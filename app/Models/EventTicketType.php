<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * De event↔tickettype-pivot als eigen model. Draagt de echte verkoopgegevens
 * (prijs, BTW, verkoopvenster, capaciteit) en wordt bij checkout met
 * lockForUpdate() vergrendeld zodat twee kopers nooit samen het laatste
 * ticket kunnen reserveren.
 */
#[Fillable([
    'event_id',
    'ticket_type_id',
    'price',
    'vat_rate',
    'sales_end_date',
    'capacity',
    'sold_out',
    'position',
])]
class EventTicketType extends Model
{
    use HasFactory;

    protected $table = 'event_ticket_types';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'sold_out' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Aantal tickets dat capaciteit bezet (gereserveerd, betaald of ingecheckt).
     * Verlopen reserveringen zijn dan al verwijderd; terugbetaalde tickets
     * tellen niet mee en geven hun plek dus weer vrij.
     */
    public function soldCount(): int
    {
        return EventTicket::query()
            ->where('event_id', $this->event_id)
            ->where('ticket_type_id', $this->ticket_type_id)
            ->whereIn('status', TicketStatus::occupying())
            ->count();
    }

    /**
     * Resterende capaciteit; null = onbeperkt.
     */
    public function remainingCapacity(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->soldCount());
    }

    public function isSoldOut(): bool
    {
        if ($this->sold_out) {
            return true;
        }

        $remaining = $this->remainingCapacity();

        return $remaining !== null && $remaining <= 0;
    }

    public function salesOpen(): bool
    {
        return Event::ticketSalesOpenFor($this->sales_end_date);
    }
}
