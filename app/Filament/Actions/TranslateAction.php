<?php

namespace App\Filament\Actions;

use App\Jobs\TranslateRecordJob;
use App\Models\User;
use App\Services\Translation\TranslateRecord;
use App\Services\Translation\TranslationException;
use App\Support\Locale;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * De "Vertalen met AI"-knoppen in de admin, in twee smaken. Welke vertaler bij
 * welk model hoort, bepaalt {@see TranslateRecord} — de tabellen hoeven niets
 * meer mee te geven.
 *
 * - De rij-actie draait bewust synchroon: één record duurt enkele seconden en
 *   de klant ziet meteen het resultaat (of de fout).
 * - De bulk-actie draait als queued batch: tientallen records na elkaar in één
 *   HTTP-request liepen op Combell tegen de request-timeout aan, waardoor de
 *   bulk halverwege afgebroken werd. De klant krijgt nu meteen "gestart" te
 *   zien en een database-notificatie (belletje) zodra alles klaar is.
 */
class TranslateAction
{
    /** Rij-actie: vertaal dit ene record, meteen. */
    public static function record(string $subject = 'pagina'): Action
    {
        return Action::make('translate')
            ->icon(Heroicon::OutlinedLanguage)
            ->button()
            ->hiddenLabel()
            ->color('primary')
            ->tooltip('Vertalen met AI')
            ->visible(fn (Model $record): bool => self::isSource($record))
            ->modalHeading('Vertalen met AI')
            ->modalDescription("Claude vertaalt deze {$subject} en zet het resultaat klaar als een aparte, gekoppelde versie. Bestaat die vertaling al, dan wordt ze overschreven — eigen aanpassingen in de vertaling gaan dan verloren.")
            ->modalSubmitActionLabel('Vertalen')
            ->schema([self::localeSelect()])
            ->action(function (Model $record, array $data) use ($subject): void {
                $locale = $data['locale'];

                try {
                    app(TranslateRecord::class)->handle($record, $locale);
                } catch (TranslationException $exception) {
                    self::failure($exception->getMessage());

                    return;
                } catch (Throwable $exception) {
                    Log::error('Vertaling mislukt', ['record' => $record->getKey(), 'exception' => $exception]);
                    self::failure('Er ging iets mis bij het vertalen. Probeer het opnieuw.');

                    return;
                }

                Notification::make()
                    ->title('Vertaling klaar')
                    ->body(ucfirst($subject).' is vertaald naar het '.strtolower(Locale::name($locale)).'. Kijk ze na voor je ze deelt.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Bulk-actie: zet per aangevinkt record een job in de wachtrij. De
     * klaar-melding komt als database-notificatie zodra de batch af is, met
     * de namen van wat eventueel mislukte.
     */
    public static function bulk(string $subjectPlural = 'pagina\'s'): BulkAction
    {
        return BulkAction::make('translate')
            ->label('Vertalen met AI')
            ->icon(Heroicon::OutlinedLanguage)
            ->color('primary')
            ->modalHeading('Vertalen met AI')
            ->modalDescription("Claude vertaalt de geselecteerde {$subjectPlural} op de achtergrond. Bestaande vertalingen worden overschreven. Je krijgt een melding zodra alles klaar is.")
            ->modalSubmitActionLabel('Vertalen')
            ->schema([self::localeSelect()])
            ->action(function (Collection $records, array $data) use ($subjectPlural): void {
                $locale = $data['locale'];

                $jobs = $records
                    ->filter(fn (Model $record): bool => self::isSource($record))
                    ->map(fn (Model $record): TranslateRecordJob => new TranslateRecordJob($record, $locale, self::labelFor($record)))
                    ->values()
                    ->all();

                if ($jobs === []) {
                    self::failure('Geen vertaalbare records in de selectie.');

                    return;
                }

                $total = count($jobs);
                $userId = auth()->id();

                Bus::batch($jobs)
                    ->name("translate-{$locale}")
                    ->allowFailures()
                    ->finally(function (Batch $batch) use ($total, $subjectPlural, $userId): void {
                        self::sendCompletionNotification($batch, $total, $subjectPlural, $userId);
                    })
                    ->dispatch();

                Notification::make()
                    ->title($total === 1 ? 'Vertaling gestart' : "{$total} vertalingen gestart")
                    ->body('De '.$subjectPlural.' worden op de achtergrond vertaald naar het '.strtolower(Locale::name($locale)).'. Je krijgt hier een melding zodra alles klaar is.')
                    ->info()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * De klaar-melding in het belletje. Statisch en met losse argumenten (geen
     * closures over objecten) omdat de batch-callback geserialiseerd wordt en
     * pas later op de queue-worker draait.
     */
    public static function sendCompletionNotification(Batch $batch, int $total, string $subjectPlural, ?int $userId): void
    {
        $user = $userId === null ? null : User::find($userId);

        if ($user === null) {
            return;
        }

        $failedLabels = Cache::pull(TranslateRecordJob::failedLabelsCacheKey($batch->id), []);
        $failed = max($batch->failedJobs, count($failedLabels));
        $done = max($total - $failed, 0);

        if ($failed > 0) {
            $names = $failedLabels === [] ? '' : ' Niet gelukt: '.implode(', ', $failedLabels).'.';

            Notification::make()
                ->title("{$done} van {$total} vertaald")
                ->body(trim($names.' Probeer de mislukte items opnieuw via de vertaalknop op hun rij.'))
                ->warning()
                ->sendToDatabase($user);

            return;
        }

        Notification::make()
            ->title($total === 1 ? 'Vertaling klaar' : "{$total} vertalingen klaar")
            ->body('Kijk de '.$subjectPlural.' na voor je ze publiceert.')
            ->success()
            ->sendToDatabase($user);
    }

    /** Weergavenaam voor in de klaar-melding: titel, naam of desnoods het id. */
    private static function labelFor(Model $record): string
    {
        return (string) ($record->getAttribute('title')
            ?? $record->getAttribute('name')
            ?? $record->getKey());
    }

    /**
     * Alleen originelen zijn vertaalbaar. Een vertaling van een vertaling zou
     * de tekst een tweede keer door het model halen en steeds verder laten
     * afdrijven van wat de klant geschreven heeft.
     */
    private static function isSource(Model $record): bool
    {
        return $record->getAttribute('translation_of') === null
            && Locale::others($record->getAttribute('locale') ?? Locale::DEFAULT) !== [];
    }

    private static function localeSelect(): Select
    {
        $options = [];

        foreach (Locale::others(Locale::DEFAULT) as $locale) {
            $options[$locale] = Locale::name($locale);
        }

        // Alfabetisch, conform de dropdown-conventie; bij één taal valt de keuze
        // sowieso weg omdat het veld dan al ingevuld is.
        asort($options);

        return Select::make('locale')
            ->label('Vertalen naar')
            ->options($options)
            ->default(array_key_first($options))
            ->required()
            ->native(false);
    }

    private static function failure(string $message): void
    {
        Notification::make()
            ->title('Vertalen mislukt')
            ->body($message)
            ->danger()
            ->persistent()
            ->send();
    }
}
