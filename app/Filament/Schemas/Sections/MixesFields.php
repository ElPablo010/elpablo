<?php

namespace App\Filament\Schemas\Sections;

use App\Filament\Schemas\Components\MediaPickerField;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * Mixes — muziek/sets van de DJ. Elke mix is een cover met titel, platform en een
 * link naar de set (SoundCloud/Mixcloud/Spotify/YouTube). Bewijs van kunnen: een
 * DJ leeft van z'n sets.
 */
class MixesFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(),

            Repeater::make('items')
                ->label('Mixes / sets')
                ->collapsible()
                ->collapsed()
                ->collapseAllAction(RepeaterToggleStyle::make())
                ->expandAllAction(RepeaterToggleStyle::make())
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            TextInput::make('title')
                                ->label('Titel')
                                ->required()
                                ->maxLength(160),
                            // Platforms alfabetisch (UX-conventie).
                            Select::make('platform')
                                ->label('Platform')
                                ->options([
                                    'mixcloud' => 'Mixcloud',
                                    'soundcloud' => 'SoundCloud',
                                    'spotify' => 'Spotify',
                                    'youtube' => 'YouTube',
                                ])
                                ->default('soundcloud')
                                ->required(),
                        ]),
                    TextInput::make('subtitle')
                        ->label('Ondertitel (optioneel)')
                        ->placeholder('bv. Reggaeton & Latin House · 60 min')
                        ->maxLength(160),
                    TextInput::make('url')
                        ->label('Link naar de set')
                        ->url()
                        ->required()
                        ->placeholder('https://soundcloud.com/...'),
                    MediaPickerField::make('cover', 'Cover-afbeelding', required: false),
                ])
                ->columns(1)
                ->defaultItems(0)
                ->reorderable(),

            // Optionele knop(pen) onder de grid, bv. "Bekijk alle sets" → Muziek-pagina.
            CtaLinkSchema::repeater(),
        ];
    }
}
