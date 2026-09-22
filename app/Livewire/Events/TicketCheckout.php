<?php

namespace App\Livewire\Events;

use App\Enums\TicketDiscountType;
use App\Exceptions\CheckoutException;
use App\Livewire\Concerns\PersistsLocale;
use App\Models\Event;
use App\Models\EventExtra;
use App\Models\EventTicketType;
use App\Services\DiscountCodeValidator;
use App\Services\TicketCheckoutService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * De ticketcheckout op de event-detailpagina. Alle prijsberekening gebeurt
 * server-side via Event::lineTotalFor() — Livewire re-rendert bij elke
 * wijziging, dus er bestaat géén JavaScript-spiegel van de prijslogica.
 *
 * Naast tickets kan een event EXTRA'S aanbieden (een gratis groepstafel, een
 * drankkaart). Die zijn geen tickets: ze tellen niet mee als bezoeker en komen
 * pas vrij vanaf een instelbaar aantal tickets in de selectie.
 *
 * De submit maakt via TicketCheckoutService een Stripe Checkout-sessie aan
 * (met capaciteitsreservering) en stuurt de bezoeker naar Stripe.
 */
class TicketCheckout extends Component
{
    use PersistsLocale;

    /**
     * Maximum per bestelling, per tickettype — houdt reserveringen behapbaar.
     * Ruim genoeg voor groepsbestellingen (een "koop 5, 1 gratis"-ladder loopt
     * tot 24 tickets); daarboven is het een gesprek, geen webshop.
     */
    public const MAX_PER_TYPE = 30;

    public int $eventId;

    /** @var array<int|string, int> ticket_type_id => aantal */
    public array $quantities = [];

    /** @var array<int|string, int> event_extra_id => aantal */
    public array $selectedExtras = [];

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

        // Alle aantallen op 0 zetten, zodat de invoervelden een waarde hebben
        // om aan te binden (een ontbrekende sleutel toont een leeg veld).
        $this->quantities = $event->eventTicketTypes
            ->mapWithKeys(fn (EventTicketType $pivot): array => [$pivot->ticket_type_id => 0])
            ->all();

        $this->selectedExtras = $event->extras
            ->mapWithKeys(fn (EventExtra $extra): array => [$extra->id => 0])
            ->all();
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
            ->with(['eventTicketTypes.ticketType', 'ticketTypes', 'ticketDiscounts', 'translations', 'extras'])
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
                ->firstWhere('type', TicketDiscountType::BuyXGetY);

            return [
                'pivot' => $pivot,
                'name' => $pivot->ticketType->nameFor($this->locale),
                'price' => $price,
                'buyable' => $buyable,
                'sales_open' => $pivot->salesOpen(),
                'sales_pending' => $pivot->salesPending(),
                'sales_starts_on' => $pivot->sales_start_date
                    ? Carbon::parse($pivot->sales_start_date)->format('d/m/Y')
                    : null,
                'sold_out' => $pivot->isSoldOut(),
                'remaining' => $remaining,
                'quantity' => $this->quantityFor($pivot->ticket_type_id),
                'max' => $this->maxQuantityFor($pivot),
                'bogo' => $bogo,
            ];
        })->all();
    }

    public function quantityFor(int $ticketTypeId): int
    {
        return max(0, (int) ($this->quantities[$ticketTypeId] ?? 0));
    }

    /** Het plafond voor dit tickettype: het ordermaximum, begrensd door de voorraad. */
    private function maxQuantityFor(EventTicketType $pivot): int
    {
        $max = self::MAX_PER_TYPE;

        if (($remaining = $pivot->remainingCapacity()) !== null) {
            $max = min($max, $remaining);
        }

        return max(0, $max);
    }

    /**
     * Het aantal per tickettype, zoals het in de selectie mag staan. Zowel de
     * plus-knop als het invoerveld lopen hierlangs — een bezoeker die "999"
     * typt krijgt gewoon het maximum.
     */
    private function clampQuantity(int $ticketTypeId, int $value): int
    {
        $pivot = $this->event->eventTicketTypes->firstWhere('ticket_type_id', $ticketTypeId);

        if (! $pivot || ! $pivot->salesOpen() || $pivot->isSoldOut() || $this->event->isCancelled()) {
            return 0;
        }

        return max(0, min($this->maxQuantityFor($pivot), $value));
    }

    /** Het invoerveld naast de stepper: clampen en de extra's herijken. */
    public function updatedQuantities(mixed $value, ?string $key = null): void
    {
        if ($key !== null) {
            $this->quantities[$key] = $this->clampQuantity((int) $key, (int) $value);
        }

        $this->pruneExtras();
    }

    public function increment(int $ticketTypeId): void
    {
        $this->quantities[$ticketTypeId] = $this->clampQuantity($ticketTypeId, $this->quantityFor($ticketTypeId) + 1);
        $this->pruneExtras();
    }

    public function decrement(int $ticketTypeId): void
    {
        $this->quantities[$ticketTypeId] = max(0, $this->quantityFor($ticketTypeId) - 1);
        $this->pruneExtras();
    }

    /* -----------------------------------------------------------------
     |  Extra's
     | ----------------------------------------------------------------- */

    /**
     * De extra's van dit event met hun status. Een extra die de drempel nog
     * niet haalt blijft zichtbaar — mét "vanaf N tickets" — want dat is net de
     * reden om er tickets bij te nemen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getExtrasProperty(): array
    {
        if ($this->event->isCancelled()) {
            return [];
        }

        return $this->event->extras->map(function (EventExtra $extra) {
            $unlocked = $extra->isUnlockedBy($this->quantities);
            $soldOut = $extra->isSoldOut();

            return [
                'model' => $extra,
                'name' => $extra->nameFor($this->locale),
                'description' => $extra->descriptionFor($this->locale),
                'price' => (float) $extra->price,
                'unlocked' => $unlocked,
                'sold_out' => $soldOut,
                'selectable' => $unlocked && ! $soldOut,
                'remaining' => $extra->remainingCapacity(),
                'min_tickets' => $extra->min_tickets,
                'ticket_type_name' => $extra->ticket_type_id
                    ? $this->event->eventTicketTypes
                        ->firstWhere('ticket_type_id', $extra->ticket_type_id)?->ticketType?->nameFor($this->locale)
                    : null,
                'quantity' => $this->extraQuantityFor($extra->id),
                'max' => $extra->maxSelectable(),
            ];
        })->all();
    }

    public function extraQuantityFor(int $extraId): int
    {
        return max(0, (int) ($this->selectedExtras[$extraId] ?? 0));
    }

    /** Vinkje voor een extra met maximum 1; de stepper gebruikt increment/decrement. */
    public function toggleExtra(int $extraId): void
    {
        $this->selectedExtras[$extraId] = $this->extraQuantityFor($extraId) > 0
            ? 0
            : $this->clampExtra($extraId, 1);
    }

    public function incrementExtra(int $extraId): void
    {
        $this->selectedExtras[$extraId] = $this->clampExtra($extraId, $this->extraQuantityFor($extraId) + 1);
    }

    public function decrementExtra(int $extraId): void
    {
        $this->selectedExtras[$extraId] = max(0, $this->extraQuantityFor($extraId) - 1);
    }

    /**
     * Een gekozen aantal binnen de grenzen van één extra: drempel gehaald,
     * niet volzet, en niet boven het maximum per bestelling of de voorraad.
     */
    private function clampExtra(int $extraId, int $value): int
    {
        $extra = $this->event->extras->firstWhere('id', $extraId);

        if (! $extra || $extra->isSoldOut() || ! $extra->isUnlockedBy($this->quantities) || $this->event->isCancelled()) {
            return 0;
        }

        return max(0, min($extra->maxSelectable(), $value));
    }

    /**
     * Zakt het ticketaantal onder de drempel (of raakt de voorraad op), dan valt
     * de extra er vanzelf af — anders zou de checkout een keuze tonen die de
     * server daarna weigert.
     */
    private function pruneExtras(): void
    {
        foreach (array_keys($this->selectedExtras) as $extraId) {
            $this->selectedExtras[$extraId] = $this->clampExtra((int) $extraId, $this->extraQuantityFor((int) $extraId));
        }
    }

    /**
     * De gekozen extra's als regels, met hun prijs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getExtraLinesProperty(): array
    {
        $lines = [];

        foreach ($this->event->extras as $extra) {
            $qty = $this->extraQuantityFor($extra->id);
            if ($qty < 1) {
                continue;
            }

            $lines[] = [
                'extra_id' => $extra->id,
                'name' => $extra->nameFor($this->locale),
                'quantity' => $qty,
                'unit_inc_vat' => (float) $extra->price,
                'total_inc_vat' => round((float) $extra->price * $qty, 2),
            ];
        }

        return $lines;
    }

    public function getExtrasTotalProperty(): float
    {
        return round(array_sum(array_column($this->extraLines, 'total_inc_vat')), 2);
    }

    /* -----------------------------------------------------------------
     |  Totalen
     | ----------------------------------------------------------------- */

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

    /** Het tickettotaal — de basis waarop een kortingscode rekent. */
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

    /**
     * Kortingscodes rekenen op de tickets; de extra's komen er daarna bij. Zo
     * kan een code nooit "20% korting op een gratis tafel" worden.
     */
    public function getTotalProperty(): float
    {
        return round(max(0, $this->subtotal - $this->discountAmount) + $this->extrasTotal, 2);
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

        $extras = [];
        foreach ($this->event->extras as $extra) {
            if (($qty = $this->extraQuantityFor($extra->id)) > 0) {
                $extras[$extra->id] = $qty;
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
                extras: $extras,
            );
        } catch (CheckoutException $e) {
            throw ValidationException::withMessages(['quantities' => $e->getMessage()]);
        }

        $this->redirect($url);
    }

    public function render()
    {
        return view('livewire.events.ticket-checkout');
    }
}
