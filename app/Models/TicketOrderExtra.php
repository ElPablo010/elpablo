<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén geclaimde extra op een bestelling (bv. "1 × Groepstafel"). Draagt bewust
 * geen eigen status: of de claim voorraad bezet volgt uit de bestelling — zie
 * EventExtra::claimedCount().
 */
#[Fillable([
    'ticket_order_id',
    'event_extra_id',
    'description',
    'quantity',
    'unit_price_inc_vat',
    'vat_rate',
    'line_total_inc_vat',
])]
class TicketOrderExtra extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_inc_vat' => 'decimal:4',
            'vat_rate' => 'decimal:2',
            'line_total_inc_vat' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id');
    }

    public function extra(): BelongsTo
    {
        return $this->belongsTo(EventExtra::class, 'event_extra_id');
    }
}
