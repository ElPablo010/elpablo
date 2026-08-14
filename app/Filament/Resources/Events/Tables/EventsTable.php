<?php

namespace App\Filament\Resources\Events\Tables;

use App\Models\Event;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('tickets'))
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Event $record): ?string => $record->venue_name),
                TextColumn::make('start_date')
                    ->label('Datum')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('published')
                    ->label('Gepubliceerd')
                    ->boolean(),
                TextColumn::make('cancelled_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (): string => 'Afgelast')
                    ->color('danger')
                    ->placeholder(''),
                TextColumn::make('updated_at')
                    ->label('Gewijzigd')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('published')
                    ->label('Gepubliceerd'),
                TernaryFilter::make('upcoming')
                    ->label('Aankomend')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->upcoming(),
                        false: fn (Builder $query): Builder => $query
                            ->whereDate('start_date', '<', today())
                            ->where(fn (Builder $q) => $q->whereNull('end_date')->orWhereDate('end_date', '<', today())),
                    ),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Bekijk op site')
                    ->icon(Heroicon::OutlinedEye)
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bekijk op site')
                    ->url(fn (Event $record): string => $record->publicUrl()),
                EditAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bewerken'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
