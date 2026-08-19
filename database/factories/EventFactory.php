<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->sentence(3);

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'start_date' => now()->addMonth()->toDateString(),
            'start_time' => '22:00:00',
            'end_time' => '04:00:00',
            'venue_name' => fake()->company(),
            'venue_postal_code' => '2000',
            'venue_city' => 'Antwerpen',
            'published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(['published' => false]);
    }

    public function cancelled(string $message = 'Afgelast wegens omstandigheden.'): static
    {
        return $this->state([
            'cancelled_at' => now(),
            'cancellation_message' => $message,
        ]);
    }
}
