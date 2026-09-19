<?php

namespace App\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Een extra bij een bestelling — een gratis groepstafel, later bijvoorbeeld een
 * drankkaart. Bewust géén tickettype: een extra maakt geen rij in event_tickets
 * aan, krijgt geen QR-PDF en telt dus nooit mee als bezoeker. Ze wordt pas
 * aanklikbaar vanaf min_tickets tickets in de bestelling (eventueel enkel van
 * één tickettype) en heeft een eigen voorraad.
 */
#[Fillable([
    'event_id',
    'name',
    'name_en',
    'name_es',
    'description',
    'description_en',
    'description_es',
    'price',
    'vat_rate',
    'capacity',
    'max_per_order',
    'min_tickets',
    'ticket_type_id',
    'sold_out',
    'position',
])]
class EventExtra extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'capacity' => 'integer',
            'max_per_order' => 'integer',
            'min_tickets' => 'integer',
            'sold_out' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** Het tickettype waarop de drempel telt; leeg = alle tickets samen. */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(TicketOrderExtra::class);
    }

    /* -----------------------------------------------------------------
     |  Taal
     | ----------------------------------------------------------------- */

    public function nameFor(?string $locale = null): string
    {
        $locale ??= Locale::current();

        return match ($locale) {
            'en' => $this->name_en ?: $this->name,
            'es' => $this->name_es ?: $this->name,
            default => $this->name,
        };
    }

    public function descriptionFor(?string $locale = null): ?string
    {
        $locale ??= Locale::current();

        return match ($locale) {
            'en' => $this->description_en ?: $this->description,
            'es' => $this->description_es ?: $this->description,
            default => $this->description,
        };
    }

    /* -----------------------------------------------------------------
     |  Voorraad
     | ----------------------------------------------------------------- */

    /**
     * Aantal geclaimde exemplaren dat voorraad bezet. Afgeleid uit de STATUS VAN
     * DE BESTELLING (betaald of nog geldige reservering) — daarom geeft een
     * verlopen reservering of terugbetaling de voorraad vanzelf vrij.
     */
    public function claimedCount(): int
    {
        return (int) TicketOrderExtra::query()
            ->where('event_extra_id', $this->id)
            ->whereHas('order', fn ($query) => $query->occupying())
            ->sum('quantity');
    }

    /** Resterende voorraad; null = onbeperkt. */
    public function remainingCapacity(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->claimedCount());
    }

    public function isSoldOut(): bool
    {
        if ($this->sold_out) {
            return true;
        }

        $remaining = $this->remainingCapacity();

        return $remaining !== null && $remaining <= 0;
    }

    /* -----------------------------------------------------------------
     |  Drempel
     | ----------------------------------------------------------------- */

    /**
     * Het aantal tickets dat voor déze extra meetelt: alle tickets in de
     * selectie, of enkel die van het gekoppelde tickettype.
     *
     * @param  array<int|string, int>  $quantities  ticket_type_id => aantal
     */
    public function countingTickets(array $quantities): int
    {
        if ($this->ticket_type_id !== null) {
            return max(0, (int) ($quantities[$this->ticket_type_id] ?? 0));
        }

        return (int) array_sum(array_map(fn ($qty) => max(0, (int) $qty), $quantities));
    }

    /**
     * Of de drempel gehaald is. Zonder drempel (0) is de extra altijd open.
     *
     * @param  array<int|string, int>  $quantities  ticket_type_id => aantal
     */
    public function isUnlockedBy(array $quantities): bool
    {
        if ($this->min_tickets < 1) {
            return true;
        }

        return $this->countingTickets($quantities) >= $this->min_tickets;
    }

    /** Hoeveel er van deze extra maximaal in één bestelling mag. */
    public function maxSelectable(): int
    {
        $max = max(1, $this->max_per_order);

        if (($remaining = $this->remainingCapacity()) !== null) {
            $max = min($max, $remaining);
        }

        return max(0, $max);
    }
}
