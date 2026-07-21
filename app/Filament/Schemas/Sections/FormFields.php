<?php

namespace App\Filament\Schemas\Sections;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;

/**
 * Formulier-sectie — een tekstkolom met een formulier ernaast of eronder.
 * `form_type` kiest wélk formulier getoond wordt; `form_layout` bepaalt hoe het
 * t.o.v. de tekst staat op brede schermen. Boventitel/titel/tekst komen (zoals
 * bij de andere secties) via HeadingFields.
 *
 * Een nieuw formuliertype toevoegen (bv. offerteaanvraag) = vier plekken:
 *   1. Livewire-component App\Livewire\Forms\<Type>Form (kopie van ContactForm).
 *   2. de form_type-dropdown hieronder — alfabetisch invoegen.
 *   3. FormSubmission::TYPE_LABELS.
 *   4. de match() in resources/views/components/site/sections/form.blade.php.
 */
class FormFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(headingRequired: false),

            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('form_type')
                        ->label('Formulier')
                        // Alfabetisch op label.
                        ->options([
                            'booking' => 'Boekingsformulier',
                            'contact' => 'Contactformulier',
                        ])
                        ->default('contact')
                        ->required()
                        ->selectablePlaceholder(false),
                    Select::make('form_layout')
                        ->label('Layout (op brede schermen)')
                        // Alfabetisch op label: Links, Onder, Rechts.
                        ->options([
                            'left' => 'Links van de tekst',
                            'below' => 'Onder de tekst',
                            'right' => 'Rechts van de tekst',
                        ])
                        ->default('right')
                        ->required()
                        ->selectablePlaceholder(false),
                ]),
        ];
    }
}
