<?php

namespace Database\Factories;

use App\Enums\DiscountCodeType;
use App\Models\DiscountCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DiscountCode>
 */
class DiscountCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'type' => DiscountCodeType::Percentage,
            'value' => 10,
            'is_active' => true,
        ];
    }

    public function fixed(float $value, bool $perTicket = false): static
    {
        return $this->state([
            'type' => DiscountCodeType::Fixed,
            'value' => $value,
            'per_ticket' => $perTicket,
        ]);
    }
}
