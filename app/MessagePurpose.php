<?php

namespace App;

enum MessagePurpose: string
{
    case Invitation = 'invitation';
    case Reminder = 'reminder';
    case Thanks = 'thanks';

    public function label(): string
    {
        return __('labels.purposes.'.$this->value);
    }
}
