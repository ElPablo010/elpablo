<?php

namespace App\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'name_en',
    'name_es',
    'default_price',
    'default_vat_rate',
    'position',
])]
class TicketType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'default_vat_rate' => 'decimal:2',
        ];
    }

    /**
     * De naam in de gevraagde taal, met terugval op de NL-naam.
     */
    public function nameFor(?string $locale = null): string
    {
        $locale ??= Locale::current();

        return match ($locale) {
            'en' => $this->name_en ?: $this->name,
            'es' => $this->name_es ?: $this->name,
            default => $this->name,
        };
    }
}
