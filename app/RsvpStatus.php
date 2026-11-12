<?php

namespace App;

enum RsvpStatus: string
{
    case Pending = 'pending';
    case Attending = 'attending';
    case Declined = 'declined';

    public function label(): string
    {
        return __('labels.rsvp.'.$this->value);
    }
}
