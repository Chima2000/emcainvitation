<?php

namespace App;

enum MessageChannel: string
{
    case Whatsapp = 'whatsapp';
    case Sms = 'sms';
    case Email = 'email';

    public function label(): string
    {
        return __('labels.channels.'.$this->value);
    }

    public function isEventChannel(): bool
    {
        return $this !== self::Email;
    }
}
