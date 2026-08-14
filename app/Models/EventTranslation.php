<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'locale',
    'name',
    'short_description',
    'description',
])]
class EventTranslation extends Model
{
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Een vertaalrij telt pas als échte, schakelbare vertaling wanneer ze
     * inhoud draagt. De admin kan lege placeholder-rijen aanmaken (bv. door een
     * taaltab te openen); die mogen nooit in de taalwissel of hreflang-alternates
     * opduiken — de pagina zou gewoon op het Nederlands terugvallen.
     */
    public function hasContent(): bool
    {
        return filled($this->name) || filled($this->short_description) || filled($this->description);
    }
}
