<?php

namespace App;

enum PledgeStatus: string
{
    case Pending = 'pending';
    case Received = 'received';

    public function label(): string
    {
        return __('labels.pledges.'.$this->value);
    }
}
