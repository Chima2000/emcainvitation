<?php

namespace App;

/**
 * Queues in the order a worker should drain them: codes first, then guest messages, then the rest.
 */
enum QueueName: string
{
    case Urgent = 'sms-urgent';
    case Messages = 'messages';
    case Default = 'default';

    public function label(): string
    {
        return match ($this) {
            self::Urgent => __('Verification codes'),
            self::Messages => __('Guest SMS'),
            self::Default => __('Other tasks'),
        };
    }

    /**
     * The comma separated list a queue worker should listen to, highest priority first.
     */
    public static function workerList(): string
    {
        return implode(',', array_map(fn (self $queue): string => $queue->value, self::cases()));
    }
}
