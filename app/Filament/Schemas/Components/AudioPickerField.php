<?php

namespace App\Filament\Schemas\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/**
 * Audio-tegenhanger van MediaPickerField: een leesbaar URL-veld met een
 * upload-actie eronder.
 *
 * Waarom niet gewoon een kale FileUpload? Die kan alléén een pad op zijn eigen
 * disk weergeven. Zodra er een absolute URL in het veld staat — zoals de geseede
 * demo-sets die nog op de oude WordPress-site staan — vindt FileUpload het
 * bestand niet, toont een leeg veld, en schrijft bij opslaan `null` terug. Met
 * ->required() erbij blokkeert dat bovendien het bewaren van de héle pagina,
 * inclusief werk op andere tabs.
 *
 * Dit veld slaat, net als MediaPickerField, een URL-string op in de content-bag.
 * De frontend (mixes.blade.php) accepteert zowel een absolute URL als een pad op
 * de public disk.
 *
 * Helptekst MOET via de parameter — zie de toelichting in MediaPickerField:
 * ->helperText() overschrijft dezelfde belowContent-slot als de upload-actie.
 */
class AudioPickerField
{
    public static function make(string $name, string $label, bool $required = true, ?string $helperText = null): TextInput
    {
        $belowContent = [
            Action::make("upload_{$name}")
                ->label('Upload mp3')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->link()
                ->modalHeading('Audiobestand uploaden')
                ->modalSubmitActionLabel('Opslaan')
                ->schema([
                    // Rechtstreeks naar zijn eindbestemming: een DJ-set is al snel
                    // 80MB en die twee keer wegschrijven (tmp -> public) is zonde.
                    // Prijs daarvan: annuleert iemand de modal ná het uploaden, dan
                    // blijft er een ongebruikt bestand in website-audio staan.
                    FileUpload::make('upload')
                        ->label('Audiobestand (mp3)')
                        ->acceptedFileTypes(['audio/mpeg', 'audio/mp3'])
                        ->maxSize(102400)
                        ->required()
                        ->disk('public')
                        ->directory('website-audio'),
                ])
                ->action(function (array $data, callable $set) use ($name): void {
                    $path = is_array($data['upload']) ? array_values($data['upload'])[0] : $data['upload'];
                    $set($name, Storage::disk('public')->url($path));
                }),
        ];

        if (filled($helperText)) {
            $belowContent[] = Text::make($helperText);
        }

        $field = TextInput::make($name)
            ->label($label)
            ->readOnly()
            ->placeholder('Nog geen audiobestand gekozen')
            ->belowContent($belowContent);

        if ($required) {
            $field->required();
        }

        return $field;
    }
}
