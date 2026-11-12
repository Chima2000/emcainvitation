<?php

namespace Database\Factories;

use App\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'type' => fake()->randomElement(['wedding', 'sendoff', 'kitchen_party', 'birthday', 'corporate', 'other']),
            'status' => EventStatus::Draft,
            'host_name' => fake()->firstName(),
            'venue' => fake()->city(),
            'event_date' => now()->addMonth(),
        ];
    }
}
