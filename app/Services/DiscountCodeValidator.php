<?php

namespace App\Services;

use App\Models\DiscountCode;

/**
 * Valideert een kortingscode voor een gast-bestelling. Side-effect-vrij: het
 * gebruik wordt pas geteld wanneer een bestelling écht betaald is. Meldingen
 * zijn gebruikersgericht Nederlands en lopen via __() zodat EN/ES meevertalen.
 */
class DiscountCodeValidator
{
    /**
     * @param  float  $orderTotal  Totaal inclusief BTW, vóór de code
     * @param  int  $quantity  Aantal tickets — voor per-ticket vast-bedrag-codes
     * @return array{valid: bool, discount_code: ?DiscountCode, discount_amount: float, error: ?string}
     */
    public function validate(string $code, string $buyerEmail, float $orderTotal, int $quantity, int $eventId): array
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return $this->fail(__('Geef een kortingscode in.'));
        }

        $discountCode = DiscountCode::where('code', $code)->first();
        if (! $discountCode) {
            return $this->fail(__('Deze kortingscode bestaat niet.'));
        }

        if (! $discountCode->is_active) {
            return $this->fail(__('Deze kortingscode is niet meer actief.'));
        }

        $today = now()->startOfDay();
        if ($discountCode->valid_from && $today->lt($discountCode->valid_from)) {
            return $this->fail(__('Deze kortingscode is nog niet geldig.'));
        }
        if ($discountCode->valid_until && $today->gt($discountCode->valid_until)) {
            return $this->fail(__('Deze kortingscode is verlopen.'));
        }

        // Aan events gebonden: enkel bruikbaar bij een bestelling voor één van die events.
        $boundEvents = $discountCode->events;
        if ($boundEvents->isNotEmpty() && ! $boundEvents->contains('id', $eventId)) {
            return $this->fail(__('Deze kortingscode is enkel geldig voor :events.', [
                'events' => $this->joinLabels($boundEvents->pluck('name')->all()),
            ]));
        }

        if ($discountCode->min_order_amount !== null && $orderTotal < (float) $discountCode->min_order_amount) {
            return $this->fail(__('Deze kortingscode is enkel geldig vanaf een bestelbedrag van € :min.', [
                'min' => number_format((float) $discountCode->min_order_amount, 2, ',', '.'),
            ]));
        }

        if ($discountCode->max_uses !== null && $discountCode->usageCount() >= $discountCode->max_uses) {
            return $this->fail(__('Deze kortingscode is niet meer beschikbaar.'));
        }

        if ($discountCode->max_uses_per_email !== null
            && $discountCode->usageCount($buyerEmail) >= $discountCode->max_uses_per_email) {
            return $this->fail(__('Je hebt deze kortingscode al gebruikt.'));
        }

        return [
            'valid' => true,
            'discount_code' => $discountCode,
            'discount_amount' => $discountCode->calculateDiscount($orderTotal, $quantity),
            'error' => null,
        ];
    }

    /**
     * "a", "a en b", "a, b en c" — leesbaar in een foutmelding.
     *
     * @param  array<int, string>  $labels
     */
    private function joinLabels(array $labels): string
    {
        if (count($labels) <= 1) {
            return (string) ($labels[0] ?? '');
        }

        $last = array_pop($labels);

        return implode(', ', $labels).' '.__('en').' '.$last;
    }

    private function fail(string $error): array
    {
        return [
            'valid' => false,
            'discount_code' => null,
            'discount_amount' => 0.0,
            'error' => $error,
        ];
    }
}
