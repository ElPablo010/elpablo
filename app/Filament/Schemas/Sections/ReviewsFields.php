<?php

namespace App\Filament\Schemas\Sections;

use App\Filament\Schemas\Components\MediaPickerField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * Reviews / testimonials — social proof vlak vóór de conversie. Elke review is
 * een quote met naam, rol/locatie en optioneel een foto + score (sterren).
 */
class ReviewsFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(),

            Repeater::make('items')
                ->label('Reviews')
                ->collapsible()
                ->collapsed()
                ->collapseAllAction(RepeaterToggleStyle::make())
                ->expandAllAction(RepeaterToggleStyle::make())
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->schema([
                    Textarea::make('quote')
                        ->label('Quote')
                        ->required()
                        ->rows(3)
                        ->maxLength(600),
                    Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            TextInput::make('name')
                                ->label('Naam')
                                ->required()
                                ->maxLength(120),
                            TextInput::make('role')
                                ->label('Rol / locatie')
                                ->placeholder('bv. Organisator — Volmolen')
                                ->maxLength(160),
                        ]),
                    Select::make('rating')
                        ->label('Score (sterren)')
                        ->options([
                            '5' => '5 sterren',
                            '4' => '4 sterren',
                            '3' => '3 sterren',
                        ])
                        ->default('5'),
                    MediaPickerField::make('image', 'Foto (optioneel)', required: false),
                ])
                ->columns(1)
                ->defaultItems(0)
                ->reorderable(),
        ];
    }
}
