<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén kost van een event (bv. "DJ Carlos", "Licht & geluid"), excl. btw.
 */
#[Fillable([
    'event_id',
    'description',
    'amount',
    'position',
])]
class EventCost extends Model
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
