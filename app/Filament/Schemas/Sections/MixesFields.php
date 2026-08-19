<?php

namespace App\Filament\Schemas\Sections;

use App\Models\Mixtape;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Mixes — muziek/sets van de DJ, met een inline audiospeler (afspelen op de
 * site) en een optionele download-knop. De mixtapes zelf leven als eigen
 * posttype (Website → Mixtapes); deze sectie toont alles of een selectie.
 *
 * show_all/mixtape_ids zijn geen tekst en staan daarom in de $skipKeys van
 * TranslatesContentArrays.
 */
class MixesFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(),

            Toggle::make('show_all')
                ->label('Toon alle mixtapes')
                ->helperText('Alle gepubliceerde mixtapes, in de volgorde van Website → Mixtapes. Zet uit om zelf een selectie te kiezen.')
                ->default(true)
                ->live(),

            Select::make('mixtape_ids')
                ->label('Mixtapes')
                ->multiple()
                // Alfabetisch (UX-conventie); op de site geldt de selectievolgorde.
                ->options(fn (): array => Mixtape::query()->orderBy('title')->pluck('title', 'id')->all())
                ->helperText('De sectie toont de mixtapes in de volgorde waarin je ze hier selecteert.')
                ->visible(fn (Get $get): bool => ! ($get('show_all') ?? true))
                ->required(fn (Get $get): bool => ! ($get('show_all') ?? true)),

            // Optionele knop(pen) onder de grid, bv. "Bekijk alle sets" → Muziek-pagina.
            CtaLinkSchema::repeater(),
        ];
    }
}
