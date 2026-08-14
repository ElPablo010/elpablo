<?php

namespace App\Models;

use App\Enums\TicketDiscountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Automatische promo per event + tickettype ("early bird", "koop 3 + 1 gratis").
 * Geen code nodig; het datumvenster bepaalt wanneer ze actief is. De toepassing
 * gebeurt in Event::lineTotalFor() — laagste regeltotaal wint bij overlap.
 */
#[Fillable([
    'event_id',
    'ticket_type_id',
    'name',
    'type',
    'price',
    'buy_quantity',
    'free_quantity',
    'valid_from',
    'valid_until',
])]
class EventTicketDiscount extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => TicketDiscountType::class,
            'price' => 'decimal:2',
            'buy_quantity' => 'integer',
            'free_quantity' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
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
     * Compacte omschrijving voor admin-tabellen: "Vaste promoprijs € 12,00" of
     * "Koop 3, 1 gratis".
     */
    public function summary(): string
    {
        if ($this->type === TicketDiscountType::BuyXGetY) {
            return sprintf('Koop %d, %d gratis', $this->buy_quantity, $this->free_quantity);
        }

        return 'Vaste promoprijs € '.number_format((float) $this->price, 2, ',', '.');
    }
}
