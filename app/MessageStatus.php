<?php

namespace App;

enum MessageStatus: string
{
    /** Prepared for the host to deliver by hand through WhatsApp or the phone's SMS app. */
    case Sent = 'sent';
    case Queued = 'queued';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return __('labels.message_statuses.'.$this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Sent => 'bg-light text-dark border',
            self::Queued => 'bg-info-subtle text-info',
            self::Delivered => 'bg-success-subtle text-success',
            self::Failed => 'bg-danger-subtle text-danger',
        };
    }
}
