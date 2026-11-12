<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use App\SubscriptionPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->subDays(3);

        return [
            'user_id' => User::factory(),
            'period' => SubscriptionPeriod::Month,
            'amount' => 15000,
            'starts_at' => $startsAt,
            'ends_at' => SubscriptionPeriod::Month->endsAfter($startsAt),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
        ]);
    }
}
