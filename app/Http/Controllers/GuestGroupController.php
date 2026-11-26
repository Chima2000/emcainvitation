<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Models\Event;
use App\Models\GuestGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestGroupController extends Controller
{
    use ManagesOwnedEvent;

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->ownedEvent($request, $event);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('guest_groups', 'name')->where('event_id', $event->id),
            ],
            'guest_ids' => ['required', 'array', 'min:1'],
            'guest_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('guests', 'id')->where('event_id', $event->id),
            ],
        ]);

        $group = $event->guestGroups()->create(['name' => $validated['name']]);
        $group->guests()->sync($validated['guest_ids']);

        return redirect()
            ->route('events.show', $event)
            ->withFragment('guests')
            ->with('status', __('Group :name is ready.', ['name' => $group->name]));
    }

    public function destroy(Request $request, Event $event, GuestGroup $group): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $group);

        $group->delete();

        return redirect()
            ->route('events.show', $event)
            ->withFragment('guests')
            ->with('status', __('Group removed.'));
    }
}
