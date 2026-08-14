<?php

namespace App\Filament\Resources\TicketOrders\RelationManagers;

use App\Enums\TicketStatus;
use App\Models\EventTicket;
use App\Services\EventTicketPdf;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    protected static ?string $title = 'Tickets';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('token')
            ->columns([
                TextColumn::make('token')
                    ->label('Token')
                    ->fontFamily('mono')
                    ->copyable(),
                TextColumn::make('ticketType.name')
                    ->label('Type'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('checked_in_at')
                    ->label('Ingecheckt om')
                    ->state(fn (EventTicket $record): ?string => $record->checkedInAtLocal()?->format('d/m/Y H:i'))
                    ->placeholder('—'),
            ])
            ->recordActions([
                // Handmatige, omkeerbare check-in — voor als de scanner hapert
                // of iemand zonder QR aan de deur staat.
                Action::make('toggleCheckIn')
                    ->button()
                    ->hiddenLabel()
                    ->icon(fn (EventTicket $record): Heroicon => $record->status === TicketStatus::CheckedIn
                        ? Heroicon::OutlinedArrowUturnLeft
                        : Heroicon::OutlinedCheckCircle)
                    ->color(fn (EventTicket $record): string => $record->status === TicketStatus::CheckedIn ? 'gray' : 'success')
                    ->tooltip(fn (EventTicket $record): string => $record->status === TicketStatus::CheckedIn
                        ? 'Check-in ongedaan maken'
                        : 'Handmatig inchecken')
                    ->visible(fn (EventTicket $record): bool => in_array($record->status, [TicketStatus::Paid, TicketStatus::CheckedIn], true))
                    ->action(function (EventTicket $record): void {
                        if ($record->status === TicketStatus::CheckedIn) {
                            $record->update([
                                'status' => TicketStatus::Paid,
                                'checked_in_at' => null,
                                'checked_in_by' => null,
                            ]);
                        } else {
                            $record->update([
                                'status' => TicketStatus::CheckedIn,
                                'checked_in_at' => now(),
                                'checked_in_by' => auth()->id(),
                            ]);
                        }
                    }),
                Action::make('downloadPdf')
                    ->button()
                    ->hiddenLabel()
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('primary')
                    ->tooltip('PDF downloaden')
                    ->visible(fn (EventTicket $record): bool => $record->status !== TicketStatus::Reserved)
                    ->action(function (EventTicket $record) {
                        $path = app(EventTicketPdf::class)->generate($record);

                        return Storage::disk('local')->download($path, 'ticket-'.$record->token.'.pdf');
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
