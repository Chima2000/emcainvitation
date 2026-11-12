<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\EventCommitteeRequest;
use App\Models\Committee;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventCommitteeController extends Controller
{
    use ManagesOwnedEvent;

    public function store(EventCommitteeRequest $request, Event $event): RedirectResponse
    {
        $attributes = $request->validated();
        DB::transaction(function () use ($event, $attributes): void {
            $committee = $event->committees()->create([
                'name' => $attributes['name'],
                'leader_guest_id' => $attributes['leader_guest_id'],
            ]);
            $committee->guests()->sync($attributes['participant_ids']);
        });

        return back()->with('status', __('Committee created.'));
    }

    public function update(EventCommitteeRequest $request, Event $event, Committee $committee): RedirectResponse
    {
        $this->ownedChild($event, $committee);
        $attributes = $request->validated();

        DB::transaction(function () use ($committee, $attributes): void {
            $committee->update([
                'name' => $attributes['name'],
                'leader_guest_id' => $attributes['leader_guest_id'],
            ]);
            $committee->guests()->sync($attributes['participant_ids']);
        });

        return back()->with('status', __('Committee updated.'));
    }

    public function destroy(Request $request, Event $event, Committee $committee): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $committee);

        $committee->delete();

        return back()->with('status', __('Committee removed. Its budget lines remain in the event budget.'));
    }
}
