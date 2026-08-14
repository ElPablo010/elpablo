<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTicket>
 */
class EventTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_type_id' => TicketType::factory(),
            'ticket_order_id' => TicketOrder::factory(),
            'status' => TicketStatus::Paid,
        ];
    }

    public function reserved(): static
    {
        return $this->state(['status' => TicketStatus::Reserved]);
    }
}
