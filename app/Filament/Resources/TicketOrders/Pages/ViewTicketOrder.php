<?php

namespace App\Filament\Resources\TicketOrders\Pages;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Filament\Resources\TicketOrders\TicketOrderResource;
use App\Jobs\SendTicketOrderEmailJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;

class ViewTicketOrder extends ViewRecord
{
    protected static string $resource = TicketOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resend')
                ->label('Tickets opnieuw verzenden')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('primary')
                ->visible(fn (): bool => $this->getRecord()->status === OrderStatus::Paid)
                ->requiresConfirmation()
                ->modalHeading('Tickets opnieuw verzenden?')
                ->modalDescription(fn (): string => 'De bevestigingsmail met alle ticket-PDF\'s wordt opnieuw gestuurd naar '.$this->getRecord()->buyer_email.'.')
                ->action(function (): void {
                    SendTicketOrderEmailJob::dispatch($this->getRecord()->id, force: true);

                    Notification::make()
                        ->title('Ticketmail wordt opnieuw verzonden')
                        ->body('De mail staat in de wachtrij (queue-worker vereist).')
                        ->success()
                        ->send();
                }),

            // Volledige terugbetaling via Stripe. De tickets worden ongeldig
            // (de scanner weigert ze) en geven hun capaciteit weer vrij.
            Action::make('refund')
                ->label('Terugbetalen')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('danger')
                ->visible(fn (): bool => $this->getRecord()->status === OrderStatus::Paid
                    && filled($this->getRecord()->stripe_payment_intent_id))
                ->requiresConfirmation()
                ->modalHeading('Bestelling terugbetalen?')
                ->modalDescription(fn (): string => 'Het volledige bedrag (€ '.number_format((float) $this->getRecord()->total_inc_vat, 2, ',', '.').') wordt via Stripe teruggestort en alle tickets worden ongeldig. Dit kan niet ongedaan gemaakt worden.')
                ->modalSubmitActionLabel('Terugbetalen')
                ->action(function (): void {
                    $order = $this->getRecord();

                    try {
                        app(PaymentGateway::class)->createRefund($order->stripe_payment_intent_id);
                    } catch (\Throwable $e) {
                        Log::error('Stripe-refund mislukt', [
                            'ticket_order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Terugbetaling mislukt')
                            ->body('Stripe weigerde de terugbetaling: '.$e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $order->update(['status' => OrderStatus::Refunded, 'refunded_at' => now()]);
                    $order->tickets()->update(['status' => TicketStatus::Refunded]);

                    Notification::make()
                        ->title('Bestelling terugbetaald')
                        ->body('Het bedrag is via Stripe teruggestort; alle tickets zijn ongeldig gemaakt.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
