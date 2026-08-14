<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_order_id',
    'ticket_type_id',
    'description',
    'quantity',
    'free_quantity',
    'unit_price_inc_vat',
    'vat_rate',
    'line_total_inc_vat',
    'discount_name',
])]
class TicketOrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'free_quantity' => 'integer',
            'unit_price_inc_vat' => 'decimal:4',
            'vat_rate' => 'decimal:2',
            'line_total_inc_vat' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id');
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }
}
