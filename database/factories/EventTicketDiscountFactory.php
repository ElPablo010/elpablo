<?php

namespace Database\Factories;

use App\Enums\TicketDiscountType;
use App\Models\Event;
use App\Models\EventTicketDiscount;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTicketDiscount>
 */
class EventTicketDiscountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_type_id' => TicketType::factory(),
            'name' => 'Early bird',
            'type' => TicketDiscountType::FixedPrice,
            'price' => 10,
            'valid_from' => now()->subWeek()->toDateString(),
            'valid_until' => now()->addWeek()->toDateString(),
        ];
    }

    public function buyXGetY(int $buy = 3, int $free = 1): static
    {
        return $this->state([
            'name' => "Koop {$buy} + {$free} gratis",
            'type' => TicketDiscountType::BuyXGetY,
            'price' => null,
            'buy_quantity' => $buy,
            'free_quantity' => $free,
        ]);
    }
}
