<?php

namespace App\Support;

use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\EventCost;
use App\Models\EventRevenue;
use App\Models\TicketOrder;
use App\Models\TicketOrderExtra;
use App\Models\TicketOrderItem;

/**
 * Financieel resultaat van één event, alles EXCL. btw (El Pablo BV recupereert
 * de btw, dus die is geen kost en geen opbrengst).
 *
 * - Online tickets: uit de betaalde bestellingen (terugbetaald telt niet). De
 *   btw wordt per orderregel afgeleid uit het tarief van die regel. Een
 *   kortingscode geldt enkel op de tickets (zie TicketCheckoutService), dus die
 *   wordt pro rata over de ticketregels verdeeld — nooit over de extra's.
 * - Andere opbrengsten en kosten: handmatig ingegeven op de tab Resultaat.
 */
final readonly class EventResult
{
    public function __construct(
        public float $ticketRevenue,
        public float $otherRevenue,
        public float $costs,
        public int $visitors,
    ) {}

    /**
     * Gebruikt de relaties paidOrders(.items/.extras), costs en revenues en het
     * attribuut visitors_count als ze al geladen zijn (overzichtspagina), anders
     * worden ze opgehaald.
     */
    public static function for(Event $event): self
    {
        $event->loadMissing(['paidOrders.items', 'paidOrders.extras', 'costs', 'revenues']);

        $visitors = array_key_exists('visitors_count', $event->getAttributes())
            ? $event->visitors_count
            : $event->tickets()->whereIn('status', self::visitorStatuses())->count();

        return new self(
            ticketRevenue: round((float) $event->paidOrders->sum(fn (TicketOrder $order) => self::orderRevenueExclVat($order)), 2),
            otherRevenue: round((float) $event->revenues->sum(fn (EventRevenue $revenue) => (float) $revenue->amount), 2),
            costs: round((float) $event->costs->sum(fn (EventCost $cost) => (float) $cost->amount), 2),
            visitors: (int) $visitors,
        );
    }

    /**
     * Een bezoeker = een betaald (of al ingecheckt) ticket.
     *
     * @return array<int, TicketStatus>
     */
    public static function visitorStatuses(): array
    {
        return [TicketStatus::Paid, TicketStatus::CheckedIn];
    }

    public static function orderRevenueExclVat(TicketOrder $order): float
    {
        $excl = fn (float $inc, float $rate): float => $rate > 0 ? $inc / (1 + $rate / 100) : $inc;

        $ticketsInc = (float) $order->items->sum(fn (TicketOrderItem $item) => (float) $item->line_total_inc_vat);
        $ticketsExcl = (float) $order->items->sum(fn (TicketOrderItem $item) => $excl((float) $item->line_total_inc_vat, (float) $item->vat_rate));

        $discount = min((float) $order->discount_amount, $ticketsInc);
        $factor = $ticketsInc > 0 ? ($ticketsInc - $discount) / $ticketsInc : 1.0;

        $extrasExcl = (float) $order->extras->sum(fn (TicketOrderExtra $extra) => $excl((float) $extra->line_total_inc_vat, (float) $extra->vat_rate));

        return $ticketsExcl * $factor + $extrasExcl;
    }

    public function revenue(): float
    {
        return round($this->ticketRevenue + $this->otherRevenue, 2);
    }

    public function result(): float
    {
        return round($this->revenue() - $this->costs, 2);
    }

    /** Resultaat als % van de opbrengsten; null zonder opbrengsten. */
    public function margin(): ?float
    {
        return $this->revenue() > 0 ? round($this->result() / $this->revenue() * 100, 1) : null;
    }

    public function resultPerVisitor(): ?float
    {
        return $this->visitors > 0 ? round($this->result() / $this->visitors, 2) : null;
    }

    /** Gemiddelde online ticketopbrengst per bezoeker, excl. btw. */
    public function averageTicketPrice(): ?float
    {
        return $this->visitors > 0 && $this->ticketRevenue > 0 ? $this->ticketRevenue / $this->visitors : null;
    }

    /**
     * Hoeveel tickets (aan de gemiddelde prijs) je nodig hebt om de kosten te
     * dekken, na aftrek van de andere opbrengsten. Null zonder ticketverkoop.
     */
    public function breakEvenTickets(): ?int
    {
        $average = $this->averageTicketPrice();

        if ($average === null) {
            return null;
        }

        return (int) max(0, ceil(round(($this->costs - $this->otherRevenue) / $average, 6)));
    }

    public static function money(?float $amount): string
    {
        return $amount === null ? '—' : '€ '.number_format($amount, 2, ',', '.');
    }
}
