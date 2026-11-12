<?php

namespace Database\Factories;

use App\MessageChannel;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'summary' => fake()->sentence(),
            'guest_limit' => 100,
            'price' => 120000,
            'channel' => MessageChannel::Whatsapp,
            'reminder_rounds' => 0,
            'includes_thanks' => false,
            'includes_pledges' => false,
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
