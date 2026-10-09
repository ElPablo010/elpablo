<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén opbrengst van een event buiten de online ticketverkoop (bv. "Kassa",
 * "Bar"), excl. btw. De online tickets rekent EventResult zelf uit de bestellingen.
 */
#[Fillable([
    'event_id',
    'description',
    'amount',
    'position',
])]
class EventRevenue extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
