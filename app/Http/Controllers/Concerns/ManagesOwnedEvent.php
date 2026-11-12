<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait ManagesOwnedEvent
{
    protected function ownedEvent(Request $request, Event $event): void
    {
        abort_unless($request->user()?->can('view', $event) ?? false, 404);
    }

    protected function ownedChild(Event $event, Model $child): void
    {
        abort_unless($child->getAttribute('event_id') === $event->id, 404);
    }
}
