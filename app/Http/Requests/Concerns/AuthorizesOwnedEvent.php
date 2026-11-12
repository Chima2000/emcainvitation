<?php

namespace App\Http\Requests\Concerns;

use App\Models\Event;

trait AuthorizesOwnedEvent
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        if (! $event instanceof Event) {
            return $this->user()?->can('create', Event::class) ?? false;
        }

        abort_unless($this->user()?->can('update', $event) ?? false, 404);

        return true;
    }
}
