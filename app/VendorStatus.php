<?php

namespace App;

enum VendorStatus: string
{
    case Shortlisted = 'shortlisted';
    case Booked = 'booked';
    case Declined = 'declined';

    public function label(): string
    {
        return __('labels.vendors.'.$this->value);
    }
}
