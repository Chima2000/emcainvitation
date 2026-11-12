<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WalletTransaction;
use App\WalletEntryType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
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
            'type' => WalletEntryType::TopUp,
            'amount' => 50000,
            'balance_after' => 50000,
            'description' => fake()->sentence(4),
        ];
    }

    public function charge(int $amount = 3000): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => WalletEntryType::MessageCharge,
            'amount' => -$amount,
            'balance_after' => 0,
        ]);
    }
}
