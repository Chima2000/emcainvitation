<?php

namespace App\Support;

use Illuminate\Support\Str;

class AdminForm
{
    /**
     * The action a shared edit modal posted to before validation sent the admin back.
     *
     * Only same-site URLs are restored so flashed input cannot point the form elsewhere.
     */
    public static function restoredAction(string $fallback = '#'): string
    {
        $action = old('_form_action');

        if (! is_string($action) || ! Str::startsWith($action, url('/').'/')) {
            return $fallback;
        }

        return $action;
    }
}
