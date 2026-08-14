<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Duurzame overdracht tussen "Checkout-sessie aangemaakt" en de webhook: de
 * uuid reist als client_reference_id mee door Stripe, de payload vertelt de
 * fulfilment welke bestelling erbij hoort.
 */
#[Fillable([
    'uuid',
    'type',
    'payload',
])]
class PendingStripeSession extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public static function put(string $uuid, string $type, array $payload): void
    {
        static::updateOrCreate(
            ['uuid' => $uuid],
            ['type' => $type, 'payload' => $payload],
        );
    }

    public static function findByUuid(string $uuid): ?self
    {
        return static::where('uuid', $uuid)->first();
    }

    public static function forget(string $uuid): void
    {
        static::where('uuid', $uuid)->delete();
    }
}
