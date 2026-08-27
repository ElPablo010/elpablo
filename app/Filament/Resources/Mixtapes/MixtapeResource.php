<?php

namespace App\Filament\Resources\Mixtapes;

use App\Filament\Resources\Mixtapes\Pages\ManageMixtapes;
use App\Filament\Schemas\Components\AudioPickerField;
use App\Filament\Schemas\Components\MediaPickerField;
use App\Models\Mixtape;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Mixtapes/DJ-sets als eigen posttype — één catalogus, taal-onafhankelijk.
 * De mixes-sectie op een pagina kiest hieruit (alles of een selectie); de
 * volgorde hier (versleepbaar) is de volgorde op de site bij "Toon alle
 * mixtapes".
 */
class MixtapeResource extends Resource
{
    protected static ?string $model = Mixtape::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return 'mixtape';
    }

    public static function getPluralModelLabel(): string
    {
        return 'mixtapes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Mixtapes';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'md' => 2])
                ->columnSpanFull()
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

            AudioPickerField::make(
                'audio_url',
                'Audiobestand (mp3)',
                helperText: 'Upload de set als mp3. Bezoekers spelen ze rechtstreeks op de site af.',
            )->columnSpanFull(),

            MediaPickerField::make('cover_url', 'Cover-afbeelding', required: false)
                ->columnSpanFull(),

            Grid::make(['default' => 1, 'md' => 2])
                ->columnSpanFull()
                ->schema([
                    Toggle::make('allow_download')
                        ->label('Download toestaan')
                        ->helperText('Toont een download-knop naast de speler.')
                        ->default(true),
                    Toggle::make('published')
                        ->label('Gepubliceerd')
                        ->helperText('Uit = nergens op de site zichtbaar.')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('cover_url')
                    ->label('Cover')
                    ->square()
                    // Absolute URL verplicht: een relatief pad (/storage/…)
                    // behandelt Filament als bestandspad op de default-disk
                    // (local), vindt daar niets en toont een broken-icoon.
                    // url() maakt er een URL op de huidige host van en laat
                    // absolute URL's ongemoeid. Zelfde patroon als de
                    // thumbnail-kolom in WebsiteMediaTable.
                    ->getStateUsing(fn (Mixtape $record): ?string => filled($record->cover_url) ? url($record->cover_url) : null),
                \Filament\Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->description(fn (Mixtape $record): ?string => $record->subtitle),
                \Filament\Tables\Columns\IconColumn::make('allow_download')
                    ->label('Download')
                    ->boolean(),
                \Filament\Tables\Columns\ToggleColumn::make('published')
                    ->label('Gepubliceerd'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('view')
                    ->icon(Heroicon::OutlinedEye)
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bekijk op site')
                    ->url(fn (Mixtape $record): string => $record->publicUrl()),
                // Kopieert de deelbare URL client-side naar het klembord; de
                // server-side action geeft alleen de bevestigings-melding.
                \Filament\Actions\Action::make('copyLink')
                    ->icon(Heroicon::OutlinedClipboardDocument)
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Kopieer publieke link')
                    ->extraAttributes(fn (Mixtape $record): array => [
                        'x-on:click' => 'window.navigator.clipboard.writeText('.\Illuminate\Support\Js::from($record->publicUrl()).')',
                    ])
                    ->action(function (Mixtape $record): void {
                        \Filament\Notifications\Notification::make()
                            ->title('Link gekopieerd')
                            ->body($record->publicUrl())
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\EditAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bewerken')
                    ->modalSubmitActionLabel('Opslaan'),
                \Filament\Actions\DeleteAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->tooltip('Verwijderen'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMixtapes::route('/'),
        ];
    }
}
