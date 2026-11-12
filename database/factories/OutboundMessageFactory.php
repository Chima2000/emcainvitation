<?php

namespace Database\Factories;

use App\MessageChannel;
use App\MessagePurpose;
use App\MessageStatus;
use App\Models\Event;
use App\Models\OutboundMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutboundMessage>
 */
class OutboundMessageFactory extends Factory
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
            'channel' => MessageChannel::Whatsapp,
            'purpose' => MessagePurpose::Invitation,
            'body' => fake()->sentence(),
            'status' => MessageStatus::Sent,
            'cost' => 0,
            'covered_by_package' => false,
            'sent_at' => now(),
        ];
    }

    /**
     * Indicate that the message is waiting for the SMS gateway.
     */
    public function queuedSms(): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => MessageChannel::Sms,
            'status' => MessageStatus::Queued,
            'sent_at' => null,
        ]);
    }
}
