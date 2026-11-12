<?php

namespace App;

enum ServiceItem: string
{
    case Card = 'card';
    case Whatsapp = 'whatsapp';
    case Sms = 'sms';
    case Reminder = 'reminder';
    case ThankYou = 'thank_you';

    public function label(): string
    {
        return __('labels.service_items.'.$this->value);
    }

    public function description(): string
    {
        return __('labels.service_item_descriptions.'.$this->value);
    }

    public function defaultAmount(): int
    {
        return match ($this) {
            self::Card => 1000,
            self::Whatsapp => 300,
            self::Sms => 90,
            self::Reminder => 150,
            self::ThankYou => 100,
        };
    }

    public static function forChannel(MessageChannel $channel): self
    {
        return match ($channel) {
            MessageChannel::Whatsapp => self::Whatsapp,
            MessageChannel::Sms => self::Sms,
        };
    }
}
