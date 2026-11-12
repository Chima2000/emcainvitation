<?php

namespace Database\Factories;

use App\Models\InvitationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationTemplate>
 */
class InvitationTemplateFactory extends Factory
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
            'category' => fake()->randomElement(['wedding', 'sendoff', 'kitchen_party', 'birthday', 'corporate', 'other']),
            'price' => 0,
            'paper' => '#faf7f7',
            'ink' => '#16171a',
            'accent' => '#940000',
            'motif' => fake()->word(),
            'description' => fake()->sentence(),
        ];
    }
}
