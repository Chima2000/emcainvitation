<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\AddCommitteeGuestsRequest;
use App\Http\Requests\EventCommitteeRequest;
use App\Models\Committee;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventCommitteeController extends Controller
{
    use ManagesOwnedEvent;

    public function store(EventCommitteeRequest $request, Event $event): RedirectResponse
    {
        $attributes = $request->validated();
        $isDoor = $request->boolean('is_door');

        DB::transaction(function () use ($event, $attributes, $isDoor): void {
            if ($isDoor && $event->committees()->where('is_door', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'is_door' => __('This event already has a door committee.'),
                ]);
            }

            $committee = $event->committees()->create([
                'name' => $attributes['name'],
                'leader_guest_id' => $attributes['leader_guest_id'],
                'is_door' => $isDoor,
            ]);
            $committee->guests()->sync($attributes['participant_ids']);

            if ($isDoor) {
                Guest::query()
                    ->whereIn('id', $attributes['door_guest_ids'])
                    ->get()
                    ->each(fn (Guest $guest) => $this->issueDoorLink($guest));
            }
        });

        return back()->with('status', __('Committee created.'));
    }

    public function update(EventCommitteeRequest $request, Event $event, Committee $committee): RedirectResponse
    {
        $this->ownedChild($event, $committee);
        $attributes = $request->validated();

        $previousMemberIds = $committee->guests()->pluck('guests.id')->all();

        DB::transaction(function () use ($committee, $attributes): void {
            $committee->update([
                'name' => $attributes['name'],
                'leader_guest_id' => $attributes['leader_guest_id'],
            ]);
            $committee->guests()->sync($attributes['participant_ids']);
        });

        $committee->refresh();
        $participantIds = array_map('intval', $attributes['participant_ids']);

        if ($committee->is_door && $committee->door_guest_id !== null && ! in_array((int) $committee->door_guest_id, $participantIds, true)) {
            $committee->forceFill(['door_guest_id' => null])->save();
        }

        $this->closeUnusedDoors($event, $previousMemberIds);

        return back()->with('status', __('Committee updated.'));
    }

    public function addGuests(AddCommitteeGuestsRequest $request, Event $event, Committee $committee): RedirectResponse
    {
        $this->ownedChild($event, $committee);

        $committee->guests()->syncWithoutDetaching($request->validated('participant_ids'));

        return to_route('events.show', $event)
            ->withFragment('committees')
            ->with('status', __('Guests added to the committee.'));
    }

    public function destroy(Request $request, Event $event, Committee $committee): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $committee);

        $memberIds = $committee->guests()->pluck('guests.id')->all();
        $committee->delete();
        $this->closeUnusedDoors($event, $memberIds);

        return back()->with('status', __('Committee removed. Its budget lines remain in the event budget.'));
    }

    public function assignDoor(Request $request, Event $event, Committee $committee): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $committee);

        if (! $committee->is_door) {
            abort_if($event->committees()->where('is_door', true)->whereKeyNot($committee->id)->exists(), 404);
            $committee->forceFill(['is_door' => true])->save();
        }

        $guestId = $request->validate([
            'guest_id' => ['required', 'integer', Rule::exists('guests', 'id')->where('event_id', $event->id)],
        ])['guest_id'];

        abort_unless($committee->guests()->whereKey($guestId)->exists(), 404);

        $guest = Guest::query()->findOrFail($guestId);
        $this->issueDoorLink($guest);

        return $this->committeeBack($committee, __('Door link for :name is ready.', ['name' => $guest->name]));
    }

    public function closeDoor(Request $request, Event $event, Committee $committee): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $committee);
        abort_unless($committee->is_door, 404);

        $guestId = $request->validate([
            'guest_id' => ['required', 'integer', Rule::exists('guests', 'id')->where('event_id', $event->id)],
        ])['guest_id'];

        abort_unless($committee->guests()->whereKey($guestId)->exists(), 404);

        $guest = Guest::query()->whereKey($guestId)->whereNotNull('door_token')->firstOrFail();
        $guestName = $guest->name;
        $guest->forceFill(['door_token' => null])->save();

        if ((int) $committee->door_guest_id === (int) $guest->id) {
            $committee->forceFill(['door_guest_id' => null])->save();
        }

        return $this->committeeBack($committee, __(':name was removed from the door link.', [
            'name' => $guestName,
        ]));
    }

    public function releaseDoor(Request $request, Event $event, Committee $committee): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $committee);
        abort_unless($committee->is_door, 404);

        $memberIds = $committee->guests()->pluck('guests.id')->all();
        $committee->forceFill([
            'is_door' => false,
            'door_guest_id' => null,
        ])->save();
        $this->closeUnusedDoors($event, $memberIds);

        return $this->committeeBack($committee, __('This committee was removed from the door link.'));
    }

    private function committeeBack(Committee $committee, string $message): RedirectResponse
    {
        return back()
            ->withFragment('committees')
            ->with('committee_notice', $message)
            ->with('committee_form', $committee->id);
    }

    private function issueDoorLink(Guest $guest): void
    {
        if ($guest->door_token === null) {
            $guest->forceFill(['door_token' => Str::random(48)])->save();
        }
    }

    /**
     * @param  list<int>  $guestIds
     */
    private function closeUnusedDoors(Event $event, array $guestIds): void
    {
        if ($guestIds === []) {
            return;
        }

        $doorMemberIds = $event->committees()
            ->where('is_door', true)
            ->first()
            ?->guests()
            ->pluck('guests.id')
            ->all() ?? [];

        Guest::query()
            ->where('event_id', $event->id)
            ->whereIn('id', $guestIds)
            ->whereNotNull('door_token')
            ->when($doorMemberIds !== [], fn ($query) => $query->whereNotIn('id', $doorMemberIds))
            ->update(['door_token' => null]);
    }
}
