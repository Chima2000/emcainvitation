<?php

namespace App;

enum TopUpStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('labels.top_up_statuses.'.$this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning-subtle text-dark',
            self::Approved => 'bg-primary-subtle text-primary',
            self::Rejected => 'bg-light text-dark border',
        };
    }
}
