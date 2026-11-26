<?php

namespace App;

enum MessagePurpose: string
{
    case Invitation = 'invitation';
    case Reminder = 'reminder';
    case Thanks = 'thanks';
    case Pledge = 'pledge';
    case Trial = 'trial';

    public function label(): string
    {
        return __('labels.purposes.'.$this->value);
    }

    public function isEventMessage(): bool
    {
        return $this !== self::Trial && $this !== self::Pledge;
    }
}
