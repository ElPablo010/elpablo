<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventTicketType;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTicketType>
 */
class EventTicketTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_type_id' => TicketType::factory(),
            'price' => 15,
            'vat_rate' => 21,
        ];
    }
}
