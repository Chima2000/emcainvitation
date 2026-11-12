<?php

namespace App;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Completed = 'completed';

    public function label(): string
    {
        return __('labels.event_statuses.'.$this->value);
    }
}
