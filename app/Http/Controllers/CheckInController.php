<?php

namespace App\Http\Controllers;

use App\Actions\CheckInGuest;
use App\Http\Requests\StoreCheckInRequest;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function store(StoreCheckInRequest $request, Event $event, CheckInGuest $checkInGuest): RedirectResponse
    {
        $guest = $event->guestMatchingCode($request->string('code')->toString());

        if (! $guest instanceof Guest) {
            return redirect()
                ->route('events.show', $event)
                ->withFragment('check-in')
                ->with('door_message', __('No invitation matches that code.'))
                ->with('check_in', 'denied');
        }

        return $this->finish($event, $guest, $checkInGuest)
            ->withFragment('check-in');
    }

    public function scan(Request $request, string $token, CheckInGuest $checkInGuest): View|RedirectResponse
    {
        $guest = Guest::query()->where('token', $token)->firstOrFail();
        $event = $guest->event;

        abort_unless($request->user()?->can('update', $event) ?? false, 404);

        $result = $checkInGuest->handle($guest);
        $guest = $result['guest'];

        return view('check-ins.result', [
            'event' => $event,
            'guest' => $guest,
            'checkedIn' => $result['checkedIn'],
            'doorMessage' => $result['checkedIn']
                ? __(':name is checked in. Card :serial cannot be used again.', [
                    'name' => $guest->name,
                    'serial' => $guest->serial,
                ])
                : __(':name already used this card at :time.', [
                    'name' => $guest->name,
                    'time' => $guest->checked_in_at->timezone(config('app.timezone'))->translatedFormat('g:i A'),
                ]),
        ]);
    }

    private function finish(Event $event, Guest $guest, CheckInGuest $checkInGuest): RedirectResponse
    {
        $result = $checkInGuest->handle($guest);

        if (! $result['checkedIn']) {
            return redirect()
                ->route('events.show', $event)
                ->with('door_message', __(':name already used this card at :time.', [
                    'name' => $guest->name,
                    'time' => $guest->checked_in_at->timezone(config('app.timezone'))->translatedFormat('g:i A'),
                ]))
                ->with('check_in', 'denied');
        }

        return redirect()
            ->route('events.show', $event)
            ->with('door_message', __(':name is checked in. Card :serial cannot be used again.', [
                'name' => $guest->name,
                'serial' => $guest->serial,
            ]))
            ->with('check_in', 'verified');
    }
}
