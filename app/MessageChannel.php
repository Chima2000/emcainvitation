<?php

namespace App;

enum MessageChannel: string
{
    case Whatsapp = 'whatsapp';
    case Sms = 'sms';

    public function label(): string
    {
        return __('labels.channels.'.$this->value);
    }
}
