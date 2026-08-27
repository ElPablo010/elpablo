<?php

namespace App\Models;

use App\Support\Locale;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mixtape/DJ-set als eigen posttype: één globale catalogus (taal-onafhankelijk —
 * dezelfde mp3 en cover voor elke taal). De mixes-sectie verwijst ernaar via
 * show_all of een expliciete lijst mixtape_ids in zijn content-bag.
 */
#[Fillable([
    'title',
    'slug',
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
        // Nieuwe mixtapes vooraan in de (versleepbare) volgorde: de nieuwste
        // set is doorgaans wat je wilt uitlichten. Herordenen kan altijd nog.
        // De kolom is unsigned, dus niet min-1 maar de rest een plek opschuiven.
        static::creating(function (Mixtape $mixtape): void {
            if ($mixtape->position === null) {
                static::query()->increment('position');
                $mixtape->position = 0;
            }

            // Slug voor de publieke detailpagina — uniek gemaakt op basis van
            // de titel, en daarna stabiel (een deelbare link mag niet breken
            // door een titelwijziging).
            $mixtape->slug ??= static::uniqueSlug($mixtape->title);
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

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'mixtape';
        $slug = $base;

        for ($i = 2; static::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    public function localizedPath(?string $locale = null): string
    {
        return Locale::href('/mixtapes/'.$this->slug, $locale);
    }

    public function publicUrl(?string $locale = null): string
    {
        return Seo::absoluteUrl($this->localizedPath($locale));
    }
}
