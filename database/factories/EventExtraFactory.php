<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventExtra;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventExtra>
 */
class EventExtraFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => 'Groepstafel',
            'description' => 'Eén gereserveerde tafel voor je groep.',
            'price' => 0,
            'vat_rate' => 21,
            'capacity' => 8,
            'max_per_order' => 1,
            'min_tickets' => 12,
        ];
    }
}
