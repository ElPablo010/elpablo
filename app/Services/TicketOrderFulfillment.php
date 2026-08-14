<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Jobs\SendTicketOrderEmailJob;
use App\Models\PendingStripeSession;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Maakt van een geslaagde Stripe-betaling een afgeronde bestelling. Wordt door
 * TWEE kanalen aangeroepen — de webhook én de bedankpagina (fallback voor een
 * trage webhook) — en is daarom volledig idempotent: de rijvergrendeling op de
 * bestelling plus de statuscheck garanderen dat dubbele delivery niets dubbel
 * uitvoert, hooguit de (zelf ontdubbelende) ticketmail opnieuw dispatcht.
 */
class TicketOrderFulfillment
{
    public function __construct(private PaymentGateway $gateway)
    {
    }

    /**
     * Voor de bedankpagina: haal de sessie bij Stripe op en volg exact
     * hetzelfde pad als de webhook.
     */
    public function completeFromStripeSessionId(string $sessionId): ?TicketOrder
    {
        $session = $this->gateway->retrieveCheckoutSession($sessionId);

        $uuid = $session->client_reference_id ?? null;
        if (! $uuid) {
            return null;
        }

        $pending = PendingStripeSession::findByUuid($uuid);
        if (! $pending) {
            // Al verwerkt (webhook was sneller) — niets meer te doen.
            return null;
        }

        return $this->complete($session, $uuid, $pending->payload);
    }

    public function complete(object $session, string $uuid, array $payload): ?TicketOrder
    {
        $orderId = $payload['ticket_order_id'] ?? null;
        if (! $orderId) {
            Log::error('Ticketfulfilment: pending payload zonder ticket_order_id', ['uuid' => $uuid]);
            PendingStripeSession::forget($uuid);

            return null;
        }

        $order = DB::transaction(function () use ($orderId, $session) {
            /** @var TicketOrder|null $order */
            $order = TicketOrder::query()->lockForUpdate()->find($orderId);

            if (! $order) {
                Log::error('Ticketfulfilment: bestelling niet gevonden', ['ticket_order_id' => $orderId]);

                return null;
            }

            if ($order->status === OrderStatus::Paid) {
                return $order; // Idempotent: al verwerkt door het andere kanaal.
            }

            // Defensief pad: het command gaf de reservering al vrij, maar de
            // betaling kwam tóch nog binnen. Kan alleen als de marge tussen de
            // Stripe-expiry (30 min) en de lokale expiry (40 min) ooit doorbroken
            // wordt. De koper heeft betaald, dus die krijgt zijn tickets —
            // desnoods boven capaciteit — en wij een logregel om het te zien.
            if ($order->status === OrderStatus::Expired) {
                Log::warning('Ticketfulfilment: betaling voor verlopen reservering — tickets hersteld', [
                    'ticket_order_id' => $order->id,
                ]);

                foreach ($order->items as $item) {
                    for ($i = 0; $i < $item->quantity; $i++) {
                        $order->tickets()->create([
                            'event_id' => $order->event_id,
                            'ticket_type_id' => $item->ticket_type_id,
                            'status' => TicketStatus::Paid,
                        ]);
                    }
                }
            }

            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'expires_at' => null,
                'stripe_session_id' => $session->id ?? $order->stripe_session_id,
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
            ]);

            $order->tickets()
                ->where('status', TicketStatus::Reserved)
                ->update(['status' => TicketStatus::Paid]);

            return $order;
        });

        PendingStripeSession::forget($uuid);

        if ($order && $order->status === OrderStatus::Paid) {
            // De job ontdubbelt zelf (cache-key per bestelling), dus opnieuw
            // dispatchen bij dubbele delivery is bewust: zo raakt een ooit
            // verloren mail alsnog verstuurd.
            rescue(fn () => SendTicketOrderEmailJob::dispatch($order->id));
        }

        return $order;
    }
}
