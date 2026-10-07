<?php

namespace App\Filament\Pages;

use App\Services\Translation\ClaudeTranslator;
use App\Services\Translation\TranslationException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Webgoeroe\Core\Filament\Pages\ManageMenus as CoreManageMenus;
use Webgoeroe\Core\Support\Locale;

/**
 * De menu's uit webgoeroe/core (velden per taal: label_en/label_es,
 * title_en/title_es) plus de knop "Vertalen met AI" van deze site. Vervangt
 * het core-scherm (CorePlugin::make()->without(CoreManageMenus::class)).
 */
class ManageMenus extends CoreManageMenus
{
    /**
     * Vertaalt alle NL-labels en -titels naar elke andere taal en zet het
     * resultaat in het formulier — niet in de database. Zo zie je de
     * vertaling eerst en bewaar je ze zelf met Opslaan. Bestaande EN/ES-waarden
     * worden overschreven (zelfde gedrag als "Vertalen met AI" op pagina's).
     */
    public function translateWithAi(): void
    {
        $data = $this->data;
        $texts = [];

        // Sleutel = het pad in $data waar de vertaling naast komt te staan
        // (label → label_en/label_es), zodat terugschrijven een data_set() is.
        foreach (self::MENUS as $location => $config) {
            if (filled($data[$location]['title'] ?? null)) {
                $texts["{$location}.title"] = $data[$location]['title'];
            }

            foreach ($data[$location]['items'] ?? [] as $key => $item) {
                if (filled($item['label'] ?? null)) {
                    $texts["{$location}.items.{$key}.label"] = $item['label'];
                }

                foreach ($item['children'] ?? [] as $childKey => $child) {
                    if (filled($child['label'] ?? null)) {
                        $texts["{$location}.items.{$key}.children.{$childKey}.label"] = $child['label'];
                    }
                }
            }
        }

        if ($texts === []) {
            Notification::make()->title('Er staan nog geen menu-items om te vertalen.')->warning()->send();

            return;
        }

        try {
            foreach (Locale::supported() as $locale) {
                if ($locale === Locale::defaultLocale()) {
                    continue;
                }

                $translated = app(ClaudeTranslator::class)->translate(
                    $texts,
                    Locale::defaultLocale(),
                    $locale,
                    context: 'Website navigation labels and footer column headings for an Urban Latin DJ. Keep them short, like menu items.',
                );

                foreach ($translated as $path => $text) {
                    data_set($data, "{$path}_{$locale}", $text);
                }
            }
        } catch (TranslationException $exception) {
            Notification::make()->title('Vertalen mislukt')->body($exception->getMessage())->danger()->send();

            return;
        }

        $this->data = $data;

        Notification::make()
            ->title('Menu\'s vertaald.')
            ->body('Controleer de EN/ES-velden en klik op Opslaan.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('translate')
                ->label('Vertalen met AI')
                ->icon(Heroicon::OutlinedLanguage)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Menu\'s vertalen met AI')
                ->modalDescription('Alle labels en titels worden naar het Engels en Spaans vertaald. Bestaande vertalingen worden overschreven. Je controleert het resultaat en slaat daarna zelf op.')
                ->modalSubmitActionLabel('Vertalen')
                ->action(fn () => $this->translateWithAi()),
            ...parent::getHeaderActions(),
        ];
    }
}
