<?php

namespace App\Models;

use App\Enums\DiscountCodeType;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'description',
    'type',
    'value',
    'per_ticket',
    'valid_from',
    'valid_until',
    'max_uses',
    'max_uses_per_email',
    'min_order_amount',
    'is_active',
])]
class DiscountCode extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => DiscountCodeType::class,
            'value' => 'decimal:2',
            'per_ticket' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'min_order_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function setCodeAttribute(string $value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * De events waarop deze code beperkt is. Leeg = geldig voor elk event.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TicketOrder::class);
    }

    /**
     * Aantal keer echt gebruikt: enkel BETAALDE bestellingen tellen. Een
     * pending order die nooit afgerekend wordt mag een code niet opgebruiken.
     */
    public function usageCount(?string $buyerEmail = null): int
    {
        return $this->orders()
            ->where('status', OrderStatus::Paid)
            ->when($buyerEmail, fn (Builder $q) => $q->where('buyer_email', $buyerEmail))
            ->count();
    }

    public function scopeActive(Builder $query): Builder
    {
        $today = now()->startOfDay();

        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today));
    }

    /**
     * Bereken de korting voor een bestelbedrag (incl. BTW).
     *
     * @param  int  $quantity  Aantal tickets — enkel relevant voor per-ticket
     *                         vast-bedrag-codes; standaard één keer op het totaal.
     */
    public function calculateDiscount(float $orderTotal, int $quantity = 1): float
    {
        if ($this->type === DiscountCodeType::Percentage) {
            // Een percentage is per ticket of op het totaal rekenkundig identiek.
            $discount = $orderTotal * ((float) $this->value / 100);
        } else {
            $units = $this->per_ticket ? max(1, $quantity) : 1;
            $discount = (float) $this->value * $units;
        }

        return round(min($discount, $orderTotal), 2);
    }
}
