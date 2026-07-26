<?php

namespace App\Models;

use App\Support\Seo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'title',
    'slug',
    'locale',
    'translation_of',
    'is_homepage',
    'published',
    'meta_title',
    'meta_description',
    'meta_robots',
    'canonical_url',
    'is_cornerstone',
    'seo_image_url',
    'seo_image_alt',
])]
class Page extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (Page $page) {
            $page->sections()->delete();
        });
    }

    protected function casts(): array
    {
        return [
            'is_homepage' => 'boolean',
            'published' => 'boolean',
            'is_cornerstone' => 'boolean',
        ];
    }

    public function sections(): MorphMany
    {
        return $this->morphMany(PageSection::class, 'sectionable')->orderBy('position');
    }

    /**
     * Absolute URL van deze pagina op de publieke site — mét locale-prefix. Een
     * kale route('page.show') zou hier fout zijn: die kent de NL-routes en zou
     * een EN-pagina naar /over sturen in plaats van /en/over.
     */
    public function publicUrl(): string
    {
        return Seo::absoluteUrl(Seo::localizedPath($this));
    }


    public function sourceTranslation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'translation_of');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(self::class, 'translation_of');
    }
}
