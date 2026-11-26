<?php

namespace App;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Host = 'host';
    case Lead = 'lead';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super Admin'),
            self::Host => __('Host'),
            self::Lead => __('Lead'),
        };
    }
}
