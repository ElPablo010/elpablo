<?php

namespace App\Livewire\Events;

use App\Livewire\Concerns\PersistsLocale;
use App\Models\Event;
use App\Models\EventTicketType;
use App\Services\DiscountCodeValidator;
use App\Services\TicketCheckoutService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * De ticketcheckout op de event-detailpagina. Alle prijsberekening gebeurt
 * server-side via Event::lineTotalFor() — Livewire re-rendert bij elke
 * wijziging, dus er bestaat géén JavaScript-spiegel van de prijslogica.
 *
 * De submit maakt via TicketCheckoutService een Stripe Checkout-sessie aan
 * (met capaciteitsreservering) en stuurt de bezoeker naar Stripe.
 */
class TicketCheckout extends Component
{
    use PersistsLocale;

    /** Maximum per bestelling, per tickettype — houdt reserveringen behapbaar. */
    public const MAX_PER_TYPE = 10;

    public int $eventId;

    /** @var array<int|string, int> ticket_type_id => aantal */
    public array $quantities = [];

    public string $discountCode = '';

    /** De gevalideerd toegepaste code (leeg = geen). */
    public string $appliedCode = '';

    public ?string $codeError = null;

    public string $buyerName = '';

    public string $buyerEmail = '';

    /** Honeypot: bots vullen dit in, mensen zien het niet. */
    public string $website = '';

    public function mount(Event $event): void
    {
        $this->eventId = $event->id;
    }

    /** @return array<string, string> Validatieberichten (locale-bewust via __()). */
    protected function messages(): array
    {
        return [
            'buyerName.required' => __('Vul je naam in.'),
            'buyerEmail.required' => __('Vul je e-mailadres in.'),
            'buyerEmail.email' => __('Vul een geldig e-mailadres in.'),
        ];
    }

    public function getEventProperty(): Event
    {
        return Event::query()
            ->with(['eventTicketTypes.ticketType', 'ticketTypes', 'ticketDiscounts', 'translations'])
            ->findOrFail($this->eventId);
    }

    /**
     * De pivotrijen die getoond worden, met hun verkoopstatus. Uitverkochte of
     * gesloten types blijven zichtbaar (met badge) maar zijn niet bestelbaar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLinesProperty(): array
    {
        return $this->event->eventTicketTypes->map(function (EventTicketType $pivot) {
            $remaining = $pivot->remainingCapacity();
            $price = $this->event->currentPriceFor($pivot->ticket_type_id);
            $buyable = $pivot->salesOpen() && ! $pivot->isSoldOut() && ! $this->event->isCancelled();

            $bogo = $this->event->activeDiscountsFor($pivot->ticket_type_id)
                ->firstWhere('type', \App\Enums\TicketDiscountType::BuyXGetY);

            return [
                'pivot' => $pivot,
                'name' => $pivot->ticketType->nameFor($this->locale),
                'price' => $price,
                'buyable' => $buyable,
                'sales_open' => $pivot->salesOpen(),
                'sold_out' => $pivot->isSoldOut(),
                'remaining' => $remaining,
                'quantity' => $this->quantityFor($pivot->ticket_type_id),
                'bogo' => $bogo,
            ];
        })->all();
    }

    public function quantityFor(int $ticketTypeId): int
    {
        return max(0, (int) ($this->quantities[$ticketTypeId] ?? 0));
    }

    public function increment(int $ticketTypeId): void
    {
        $pivot = $this->event->eventTicketTypes->firstWhere('ticket_type_id', $ticketTypeId);
        if (! $pivot || ! $pivot->salesOpen() || $pivot->isSoldOut() || $this->event->isCancelled()) {
            return;
        }

        $max = self::MAX_PER_TYPE;
        if (($remaining = $pivot->remainingCapacity()) !== null) {
            $max = min($max, $remaining);
        }

        $this->quantities[$ticketTypeId] = min($max, $this->quantityFor($ticketTypeId) + 1);
    }

    public function decrement(int $ticketTypeId): void
    {
        $this->quantities[$ticketTypeId] = max(0, $this->quantityFor($ticketTypeId) - 1);
    }

    /**
     * @return array<int, array<string, mixed>> Regels met aantal > 0, mét prijs.
     */
    public function getOrderLinesProperty(): array
    {
        $lines = [];

        foreach ($this->event->eventTicketTypes as $pivot) {
            $qty = $this->quantityFor($pivot->ticket_type_id);
            if ($qty < 1) {
                continue;
            }

            $line = $this->event->lineTotalFor($pivot->ticket_type_id, $qty);
            $line['ticket_type_id'] = $pivot->ticket_type_id;
            $line['name'] = $pivot->ticketType->nameFor($this->locale);
            $lines[] = $line;
        }

        return $lines;
    }

    public function getSubtotalProperty(): float
    {
        return round(array_sum(array_column($this->orderLines, 'total_inc_vat')), 2);
    }

    public function getTicketCountProperty(): int
    {
        return (int) array_sum(array_column($this->orderLines, 'quantity'));
    }

    public function getDiscountAmountProperty(): float
    {
        if ($this->appliedCode === '' || $this->subtotal <= 0) {
            return 0.0;
        }

        $result = app(DiscountCodeValidator::class)->validate(
            $this->appliedCode,
            $this->buyerEmail,
            $this->subtotal,
            $this->ticketCount,
            $this->eventId,
        );

        return $result['valid'] ? $result['discount_amount'] : 0.0;
    }

    public function getTotalProperty(): float
    {
        return round(max(0, $this->subtotal - $this->discountAmount), 2);
    }

    public function applyDiscountCode(): void
    {
        $this->codeError = null;

        $result = app(DiscountCodeValidator::class)->validate(
            $this->discountCode,
            $this->buyerEmail,
            $this->subtotal,
            $this->ticketCount,
            $this->eventId,
        );

        if (! $result['valid']) {
            $this->appliedCode = '';
            $this->codeError = $result['error'];

            return;
        }

        $this->appliedCode = $result['discount_code']->code;
        $this->discountCode = '';
    }

    public function removeDiscountCode(): void
    {
        $this->appliedCode = '';
        $this->codeError = null;
    }

    public function checkout(): void
    {
        // Stille spam-afhandeling: doe alsof er niets gebeurde.
        if ($this->website !== '') {
            return;
        }

        $this->validate([
            'buyerName' => 'required|string|max:120',
            'buyerEmail' => 'required|email|max:190',
        ]);

        if ($this->ticketCount < 1) {
            throw ValidationException::withMessages([
                'quantities' => __('Selecteer minstens één ticket.'),
            ]);
        }

        $quantities = [];
        foreach ($this->event->eventTicketTypes as $pivot) {
            if (($qty = $this->quantityFor($pivot->ticket_type_id)) > 0) {
                $quantities[$pivot->ticket_type_id] = $qty;
            }
        }

        try {
            $url = app(TicketCheckoutService::class)->createSession(
                event: $this->event,
                quantities: $quantities,
                buyerName: $this->buyerName,
                buyerEmail: $this->buyerEmail,
                discountCode: $this->appliedCode !== '' ? $this->appliedCode : null,
                locale: $this->locale,
            );
        } catch (\App\Exceptions\CheckoutException $e) {
            throw ValidationException::withMessages(['quantities' => $e->getMessage()]);
        }

        $this->redirect($url);
    }

    public function render()
    {
        return view('livewire.events.ticket-checkout');
    }
}
