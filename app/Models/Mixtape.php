<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Mixtape/DJ-set als eigen posttype: één globale catalogus (taal-onafhankelijk —
 * dezelfde mp3 en cover voor elke taal). De mixes-sectie verwijst ernaar via
 * show_all of een expliciete lijst mixtape_ids in zijn content-bag.
 */
#[Fillable([
    'title',
    'subtitle',
    'audio_url',
    'cover_url',
    'allow_download',
    'published',
    'position',
])]
class Mixtape extends Model
{
    protected static function booted(): void
    {
        // Nieuwe mixtapes achteraan in de (versleepbare) volgorde.
        static::creating(function (Mixtape $mixtape): void {
            $mixtape->position ??= (int) static::query()->max('position') + 1;
        });
    }

    protected function casts(): array
    {
        return [
            'allow_download' => 'boolean',
            'published' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * Afspeelbare URL. AudioPickerField bewaart al een kant-en-klare relatieve
     * URL ('/storage/website-audio/…') en gemigreerde demo's zijn absoluut
     * ('http…'): beide ongemoeid laten. Alleen een kaal disk-pad
     * ('website-audio/…') krijgt nog de /storage-prefix — opnieuw prefixen gaf
     * '/storage/storage/…' en dus dode spelers en downloads.
     */
    public function resolvedAudioUrl(): ?string
    {
        $audio = $this->audio_url;

        if (blank($audio)) {
            return null;
        }

        if (str_starts_with($audio, 'http') || str_starts_with($audio, '/')) {
            return $audio;
        }

        return Storage::disk('public')->url($audio);
    }
}
