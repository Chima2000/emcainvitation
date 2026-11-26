<?php

namespace App\Http\Controllers;

use App\Actions\CheckInGuest;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DoorController extends Controller
{
    public function show(string $token): View
    {
        $staff = $this->staff($token);
        $event = $staff->event;

        return view('doors.show', [
            'staff' => $staff,
            'event' => $event,
            'recentCheckIns' => $event->guests()
                ->whereNotNull('checked_in_at')
                ->latest('checked_in_at')
                ->get(['name', 'serial', 'party_size', 'table_name', 'checked_in_at']),
        ]);
    }

    public function store(Request $request, string $token, CheckInGuest $checkInGuest): RedirectResponse
    {
        $staff = $this->staff($token);
        $event = $staff->event;
        $code = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ])['code'];
        $guest = $event->guestMatchingCode($code);

        if (! $guest instanceof Guest) {
            return redirect()
                ->route('doors.show', $token)
                ->with('door_message', __('No invitation matches that code.'))
                ->with('check_in', 'denied');
        }

        $result = $checkInGuest->handle($guest);
        $guest = $result['guest'];

        if (! $result['checkedIn']) {
            return redirect()
                ->route('doors.show', $token)
                ->with('door_message', __(':name already used this card at :time.', [
                    'name' => $guest->name,
                    'time' => $guest->checked_in_at->timezone(config('app.timezone'))->translatedFormat('g:i A'),
                ]))
                ->with('check_in', 'denied');
        }

        return redirect()
            ->route('doors.show', $token)
            ->with('door_message', __(':name is checked in. Card :serial cannot be used again.', [
                'name' => $guest->name,
                'serial' => $guest->serial,
            ]))
            ->with('check_in', 'verified')
            ->with('door_guest', $guest->only(['name', 'serial', 'party_size', 'table_name']));
    }

    private function staff(string $token): Guest
    {
        $staff = Guest::query()->where('door_token', $token)->with('event.user')->firstOrFail();
        $owner = $staff->event->user;

        abort_unless($owner instanceof User && ! $owner->isLead(), 404);
        abort_unless(
            $staff->committees()
                ->where('committees.event_id', $staff->event_id)
                ->where('is_door', true)
                ->exists(),
            404,
        );

        return $staff;
    }
}
