<?php

namespace Database\Factories;

use App\Models\TopUpRequest;
use App\Models\User;
use App\PaymentMethod;
use App\TopUpStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopUpRequest>
 */
class TopUpRequestFactory extends Factory
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
            'amount' => 50000,
            'method' => PaymentMethod::Mpesa,
            'reference' => strtoupper(fake()->unique()->bothify('??#?#??##?')),
            'payer_phone' => '07'.fake()->numerify('########'),
            'status' => TopUpStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TopUpStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }
}
