<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\TicketOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketOrder>
 */
class TicketOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'buyer_name' => fake()->name(),
            'buyer_email' => fake()->unique()->safeEmail(),
            'locale' => 'nl',
            'status' => OrderStatus::Pending,
            'subtotal_inc_vat' => 30,
            'total_inc_vat' => 30,
            'expires_at' => now()->addMinutes(40),
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
            'stripe_session_id' => 'cs_test_'.fake()->unique()->lexify('????????????'),
            'stripe_payment_intent_id' => 'pi_'.fake()->unique()->lexify('????????????'),
            'expires_at' => null,
        ]);
    }
}
