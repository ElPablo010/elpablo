<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Exceptions\CheckoutException;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\EventTicketType;
use App\Models\PendingStripeSession;
use App\Support\Attribution;
use App\Models\TicketOrder;
use App\Support\Locale;
use App\Support\Seo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Bouwt van een ticketselectie een Stripe Checkout-sessie, mét race-vrije
 * capaciteitsreservering:
 *
 * 1. Alles wordt server-side hervalideerd (verkoopvenster, uitverkocht,
 *    prijzen via Event::lineTotalFor(), kortingscode) — nooit client-invoer
 *    vertrouwen.
 * 2. In één transactie worden de event_ticket_types-pivotrijen in vaste
 *    volgorde vergrendeld (lockForUpdate), de capaciteit gecontroleerd en de
 *    bestelling + reserveringstickets weggeschreven. Twee gelijktijdige kopers
 *    serialiseren hier; de tweede krijgt een nette foutmelding.
 * 3. De reservering verloopt lokaal na 40 minuten; de Stripe-sessie zelf al na
 *    30 (het Stripe-minimum). Stripe kan dus nooit een betaling voltooien voor
 *    een reservering die wij al vrijgaven.
 */
class TicketCheckoutService
{
    /** Reservering verloopt ná de Stripe-sessie (30 min) — marge van 10 min. */
    public const RESERVATION_MINUTES = 40;

    public const STRIPE_SESSION_MINUTES = 30;

    /** Stripe weigert betalingen onder € 0,50. */
    public const MINIMUM_TOTAL = 0.50;

    public function __construct(
        private PaymentGateway $gateway,
        private DiscountCodeValidator $validator,
    ) {
    }

    /**
     * @param  array<int|string, int>  $quantities  ticket_type_id => aantal
     * @return string De Stripe Checkout-URL om de koper naartoe te sturen.
     *
     * @throws CheckoutException met een gebruikersgerichte, vertaalde boodschap.
     */
    public function createSession(
        Event $event,
        array $quantities,
        string $buyerName,
        string $buyerEmail,
        ?string $discountCode,
        string $locale,
    ): string {
        $event->load(['eventTicketTypes.ticketType', 'ticketTypes', 'ticketDiscounts']);

        if (! $event->published || $event->isCancelled()) {
            throw new CheckoutException(__('Dit event is niet (meer) beschikbaar.'));
        }

        // Stap 1 — lijnen hervalideren en herrekenen.
        $lines = [];
        foreach ($quantities as $ticketTypeId => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity < 1) {
                continue;
            }

            $pivot = $event->eventTicketTypes->firstWhere('ticket_type_id', (int) $ticketTypeId);
            if (! $pivot) {
                throw new CheckoutException(__('Eén van de gekozen tickettypes bestaat niet (meer) voor dit event.'));
            }

            $name = $pivot->ticketType->nameFor($locale);

            if (! $pivot->salesOpen()) {
                throw new CheckoutException(__('De verkoop voor ":type" is afgesloten.', ['type' => $name]));
            }

            $line = $event->lineTotalFor($pivot->ticket_type_id, $quantity);
            $line['name'] = $name;
            $lines[] = $line + ['pivot_id' => $pivot->id, 'ticket_type_id' => $pivot->ticket_type_id];
        }

        if ($lines === []) {
            throw new CheckoutException(__('Selecteer minstens één ticket.'));
        }

        $subtotal = round(array_sum(array_column($lines, 'total_inc_vat')), 2);
        $ticketCount = (int) array_sum(array_column($lines, 'quantity'));

        // Stap 2 — kortingscode server-side hervalideren (nooit de preview vertrouwen).
        $discountModel = null;
        $discountAmount = 0.0;
        if (filled($discountCode)) {
            $result = $this->validator->validate($discountCode, $buyerEmail, $subtotal, $ticketCount, $event->id);
            if (! $result['valid']) {
                throw new CheckoutException($result['error']);
            }
            $discountModel = $result['discount_code'];
            $discountAmount = $result['discount_amount'];
        }

        $total = round(max(0, $subtotal - $discountAmount), 2);
        if ($total < self::MINIMUM_TOTAL) {
            throw new CheckoutException(__('Het totaalbedrag moet minstens € 0,50 zijn.'));
        }

        // Stap 3 — reservering in één transactie, met rijvergrendeling.
        $uuid = (string) Str::uuid();

        $order = DB::transaction(function () use ($event, $lines, $buyerName, $buyerEmail, $locale, $subtotal, $total, $discountModel, $discountAmount, $uuid) {
            // Vergrendel de pivotrijen in vaste volgorde (geen deadlocks) —
            // dit serialiseert alle gelijktijdige checkouts per tickettype.
            $pivots = EventTicketType::query()
                ->where('event_id', $event->id)
                ->whereIn('ticket_type_id', array_column($lines, 'ticket_type_id'))
                ->orderBy('ticket_type_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('ticket_type_id');

            foreach ($lines as $line) {
                $pivot = $pivots[$line['ticket_type_id']] ?? null;
                if (! $pivot || $pivot->sold_out) {
                    throw new CheckoutException(__('":type" is uitverkocht.', ['type' => $line['name']]));
                }

                if ($pivot->capacity !== null) {
                    $sold = EventTicket::query()
                        ->where('event_id', $event->id)
                        ->where('ticket_type_id', $pivot->ticket_type_id)
                        ->whereIn('status', TicketStatus::occupying())
                        ->count();

                    $remaining = $pivot->capacity - $sold;
                    if ($line['quantity'] > $remaining) {
                        throw new CheckoutException($remaining > 0
                            ? __('Er zijn nog maar :count tickets beschikbaar voor ":type".', ['count' => $remaining, 'type' => $line['name']])
                            : __('":type" is uitverkocht.', ['type' => $line['name']]));
                    }
                }
            }

            $order = TicketOrder::create([
                'event_id' => $event->id,
                'buyer_name' => $buyerName,
                'buyer_email' => $buyerEmail,
                'locale' => $locale,
                'status' => OrderStatus::Pending,
                'subtotal_inc_vat' => $subtotal,
                'total_inc_vat' => $total,
                'discount_code_id' => $discountModel?->id,
                'discount_amount' => $discountAmount > 0 ? $discountAmount : null,
                'expires_at' => now()->addMinutes(self::RESERVATION_MINUTES),
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'ticket_type_id' => $line['ticket_type_id'],
                    'description' => $line['name'],
                    'quantity' => $line['quantity'],
                    'free_quantity' => $line['free'],
                    'unit_price_inc_vat' => $line['unit_inc_vat'],
                    'vat_rate' => $line['vat_rate'],
                    'line_total_inc_vat' => $line['total_inc_vat'],
                    'discount_name' => $line['discount']['name'] ?? null,
                ]);

                for ($i = 0; $i < $line['quantity']; $i++) {
                    $order->tickets()->create([
                        'event_id' => $event->id,
                        'ticket_type_id' => $line['ticket_type_id'],
                        'status' => TicketStatus::Reserved,
                    ]);
                }
            }

            // De webhook heeft geen bezoekerssessie: de herkomst (first touch)
            // reist daarom mee in de payload. Enkel als er een snapshot is —
            // nooit een null-sleutel injecteren.
            $payload = ['ticket_order_id' => $order->id];
            if ($attribution = Attribution::current()) {
                $payload['attribution'] = $attribution;
            }
            PendingStripeSession::put($uuid, 'event_order', $payload);

            return $order;
        });

        // Stap 4 — Stripe-sessie buiten de transactie (netwerk-I/O hoort niet
        // binnen een lock). Faalt Stripe, dan rollen we de reservering terug.
        try {
            $session = $this->gateway->createCheckoutSession([
                'mode' => 'payment',
                'payment_method_types' => ['card', 'bancontact', 'ideal', 'link', 'paypal'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => ['name' => __('Tickets: :event', ['event' => $event->name])],
                        'unit_amount' => (int) round($total * 100),
                    ],
                    'quantity' => 1,
                ]],
                'client_reference_id' => $uuid,
                'customer_email' => $buyerEmail,
                'expires_at' => now()->addMinutes(self::STRIPE_SESSION_MINUTES)->getTimestamp(),
                'success_url' => Seo::absoluteUrl(Locale::href('/events/'.$event->slug.'/bedankt', $locale)).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $event->publicUrl($locale),
            ]);
        } catch (\Throwable $e) {
            $order->tickets()->delete();
            $order->items()->delete();
            $order->delete();
            PendingStripeSession::forget($uuid);

            Log::error('Stripe Checkout-sessie aanmaken mislukt', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            throw new CheckoutException(__('De betaalpagina kon niet gestart worden. Probeer het zo meteen opnieuw.'));
        }

        $order->update(['stripe_session_id' => $session->id]);

        return $session->url;
    }
}
