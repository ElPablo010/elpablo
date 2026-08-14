<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Filament\Actions\TranslateAction;
use App\Models\Page;
use App\Support\Locale;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('title')
            // Vertaalkolom leest translations/sourceTranslation per rij — eager
            // laden, anders is het één query per pagina in de lijst.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['translations', 'sourceTranslation']))
            ->columns([
                TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    // Dé homepage herken je aan het huisje vóór de titel — een
                    // hele kolom vinkjes voor iets dat maar één keer waar is,
                    // was vooral ruis.
                    ->icon(fn (Page $record): ?Heroicon => $record->is_homepage ? Heroicon::Home : null)
                    ->iconColor('primary')
                    ->tooltip(fn (Page $record): ?string => $record->is_homepage ? 'Dit is de homepage' : null),
                TextColumn::make('locale')
                    ->label('Taal')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => strtoupper($state ?? Locale::DEFAULT))
                    ->color(fn (?string $state): string => ($state ?? Locale::DEFAULT) === Locale::DEFAULT ? 'primary' : 'gray')
                    ->sortable(),
                ViewColumn::make('translations')
                    ->label('Vertalingen')
                    ->view('filament.tables.columns.page-translations'),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('published')
                    ->label('Gepubliceerd')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Gewijzigd')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Standaard tonen we alleen de bronpagina's (NL). De vertalingen
                // hangen als badges aan die rij, wat de lijst kort houdt: bij drie
                // talen zou je anders door driemaal zoveel rijen scrollen.
                SelectFilter::make('translation_scope')
                    ->label('Talen')
                    ->options([
                        'source' => 'Alleen hoofdtaal ('.strtoupper(Locale::DEFAULT).')',
                        'all' => 'Alle talen',
                    ])
                    ->default('source')
                    ->selectablePlaceholder(false)
                    ->query(fn (Builder $query, array $data): Builder => ($data['value'] ?? 'source') === 'source'
                        ? $query->whereNull('translation_of')
                        : $query),
                TernaryFilter::make('published')
                    ->label('Gepubliceerd'),
            ])
            ->recordActions([
                TranslateAction::record(subject: 'pagina'),
                Action::make('view')
                    ->label('Bekijk op site')
                    ->icon(Heroicon::OutlinedEye)
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bekijk op site')
                    ->url(fn (Page $record): string => $record->publicUrl()),
                EditAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bewerken'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    TranslateAction::bulk(subjectPlural: 'pagina\'s'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
