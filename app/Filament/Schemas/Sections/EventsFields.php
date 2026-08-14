<?php

namespace App\Filament\Schemas\Sections;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * Events — teaser van de eerstvolgende events (uit het Events-posttype), met
 * een knop naar het volledige overzicht op /events. De sectie toont altijd de
 * eerstvolgende gepubliceerde events; er valt dus niets handmatig te selecteren.
 */
class EventsFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(headingRequired: false),

            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('limit')
                        ->label('Aantal events')
                        ->options([2 => '2', 3 => '3', 4 => '4', 6 => '6'])
                        ->default(3)
                        ->selectablePlaceholder(false),
                    TextInput::make('cta_label')
                        ->label('Knoptekst overzicht')
                        ->maxLength(120)
                        ->helperText('Leeg = "Alle events". De knop linkt altijd naar /events.'),
                ]),
        ];
    }
}
