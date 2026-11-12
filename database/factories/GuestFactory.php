<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Guest;
use App\RsvpStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->name(),
            'party_size' => 1,
            'rsvp_status' => RsvpStatus::Pending,
            'serial' => 'EM-'.fake()->unique()->numerify('######'),
            'token' => Str::random(40),
        ];
    }

    /**
     * Indicate that the guest has accepted the invitation.
     */
    public function attending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rsvp_status' => RsvpStatus::Attending,
        ]);
    }
}
