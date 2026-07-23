<?php

namespace App\Filament\Schemas\Sections;

use App\Filament\Schemas\Components\MediaPickerField;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

/**
 * Mixes — muziek/sets van de DJ, met een inline audiospeler (afspelen op de site)
 * en een optionele download-knop. Zelf-gehoste mp3's, geen platform-embeds: de
 * bezoeker speelt en downloadt zonder de site te verlaten.
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
                            TextInput::make('subtitle')
                                ->label('Ondertitel (optioneel)')
                                ->placeholder('bv. Reggaeton & Latin House · 60 min')
                                ->maxLength(160),
                        ]),

                    FileUpload::make('audio')
                        ->label('Audiobestand (mp3)')
                        ->helperText('Upload de set als mp3. Bezoekers spelen ze rechtstreeks op de site af.')
                        ->disk('public')
                        ->directory('website-audio')
                        ->acceptedFileTypes(['audio/mpeg', 'audio/mp3'])
                        ->maxSize(102400)
                        ->downloadable()
                        ->required(),

                    MediaPickerField::make('cover', 'Cover-afbeelding', required: false),

                    Toggle::make('allow_download')
                        ->label('Download toestaan')
                        ->helperText('Toont een download-knop naast de speler.')
                        ->default(true),
                ])
                ->columns(1)
                ->defaultItems(0)
                ->reorderable(),

            // Optionele knop(pen) onder de grid, bv. "Bekijk alle sets" → Muziek-pagina.
            CtaLinkSchema::repeater(),
        ];
    }
}
