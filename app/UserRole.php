<?php

namespace App;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Host = 'host';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super Admin'),
            self::Host => __('Host'),
        };
    }
}
