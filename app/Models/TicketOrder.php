<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gast-bestelling: er zijn geen klantaccounts, de koper is naam + e-mail.
 * De bestelling ontstaat al bij het aanmaken van de Stripe-sessie (Pending,
 * met reserveringstickets) en wordt bij fulfilment Betaald — zo is capaciteit
 * exact en is idempotentie een kwestie van de status controleren.
 */
#[Fillable([
    'event_id',
    'buyer_name',
    'buyer_email',
    'locale',
    'status',
    'subtotal_inc_vat',
    'total_inc_vat',
    'discount_code_id',
    'discount_amount',
    'stripe_session_id',
    'stripe_payment_intent_id',
    'expires_at',
    'paid_at',
    'refunded_at',
])]
class TicketOrder extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_inc_vat' => 'decimal:2',
            'total_inc_vat' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TicketOrderItem::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    /** Geclaimde extra's (groepstafel, …) — géén tickets, dus geen bezoekers. */
    public function extras(): HasMany
    {
        return $this->hasMany(TicketOrderExtra::class);
    }

    /**
     * Bestellingen die voorraad bezetten: betaald, of een reservering die nog
     * loopt. Dit is de enige waarheid voor de voorraad van een extra — zo geeft
     * een verlopen reservering of een terugbetaling ze vanzelf weer vrij.
     */
    public function scopeOccupying(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('status', OrderStatus::Paid)
            ->orWhere(fn (Builder $pending) => $pending
                ->where('status', OrderStatus::Pending)
                ->where('expires_at', '>', now())));
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    /**
     * Geef een verlopen reservering vrij: de gereserveerde tickets verdwijnen
     * (en tellen dus niet langer mee in de capaciteit), de bestelling wordt
     * Verlopen en de pending-Stripe-koppeling wordt opgeruimd. Alleen zinvol
     * voor Pending-bestellingen; alle andere statussen blijven onaangeroerd.
     */
    public function releaseReservation(): void
    {
        if ($this->status !== OrderStatus::Pending) {
            return;
        }

        $this->tickets()->where('status', TicketStatus::Reserved)->delete();
        $this->update(['status' => OrderStatus::Expired, 'expires_at' => null]);

        PendingStripeSession::where('payload->ticket_order_id', $this->id)->delete();
    }

    /**
     * Totale BTW afgeleid uit de regels — elke regel draagt zijn eigen tarief,
     * er staat bewust géén BTW-tarief op de header. Extra's tellen mee: ze staan
     * los van de tickets, maar wel op dezelfde factuur.
     */
    public function vatAmount(): float
    {
        $vat = fn (float $inc, float $rate): float => $rate > 0 ? $inc - ($inc / (1 + $rate / 100)) : 0.0;

        $fromItems = $this->items->sum(fn (TicketOrderItem $item) => $vat(
            (float) $item->line_total_inc_vat,
            (float) $item->vat_rate,
        ));

        $fromExtras = $this->extras->sum(fn (TicketOrderExtra $extra) => $vat(
            (float) $extra->line_total_inc_vat,
            (float) $extra->vat_rate,
        ));

        return round($fromItems + $fromExtras, 2);
    }

    /** Het totaal van de extra's (0 als er geen zijn). */
    public function extrasTotal(): float
    {
        return round((float) $this->extras->sum(fn (TicketOrderExtra $extra) => (float) $extra->line_total_inc_vat), 2);
    }
}
