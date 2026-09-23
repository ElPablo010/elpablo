<?php

namespace App\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'location',
    'name',
    'title',
    'title_en',
    'title_es',
])]
class Menu extends Model
{
    /**
     * Top-level items (geen submenu-kinderen), op volgorde.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('position');
    }

    /**
     * Alle items (parents én children), op volgorde.
     */
    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('position');
    }

    /**
     * De kop in de gevraagde taal — zelfde terugval als MenuItem::labelFor().
     */
    public function titleFor(?string $locale = null): ?string
    {
        if (blank($this->title)) {
            return null;
        }

        $locale ??= Locale::current();

        if ($locale !== Locale::DEFAULT && filled($this->{"title_{$locale}"} ?? null)) {
            return $this->{"title_{$locale}"};
        }

        return __($this->title, locale: $locale);
    }
}
