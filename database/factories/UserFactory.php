<?php

namespace Database\Factories;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+2556'.fake()->unique()->numerify('########'),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
            'subscription_ends_at' => now()->addMonth(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Host,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::SuperAdmin,
        ]);
    }

    public function withoutSubscription(): static
    {
        return $this->state(fn (array $attributes): array => [
            'subscription_ends_at' => null,
        ]);
    }

    public function subscriptionExpired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'subscription_ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
