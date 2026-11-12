<?php

namespace App\Http\Controllers;

use App\Actions\ImportGuests;
use App\Actions\IssueGuestPass;
use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\ImportGuestsRequest;
use App\Http\Requests\StoreGuestRequest;
use App\Http\Requests\UpdateGuestRequest;
use App\Models\Event;
use App\Models\Guest;
use App\RsvpStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    use ManagesOwnedEvent;

    public function store(StoreGuestRequest $request, Event $event, IssueGuestPass $issueGuestPass): RedirectResponse
    {
        $issueGuestPass->handle($event, [
            ...$request->safe()->except(['rsvp_status']),
            'rsvp_status' => $request->enum('rsvp_status', RsvpStatus::class) ?? RsvpStatus::Pending,
        ]);

        return redirect()
            ->route('events.show', $event)
            ->with('status', __('Guest added with a one-time card number.'));
    }

    public function edit(Request $request, Event $event, Guest $guest): View
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $guest);

        return view('guests.edit', [
            'event' => $event,
            'guest' => $guest,
            'statuses' => RsvpStatus::cases(),
        ]);
    }

    public function update(UpdateGuestRequest $request, Event $event, Guest $guest): RedirectResponse
    {
        $this->ownedChild($event, $guest);

        $guest->update([
            ...$request->safe()->except(['rsvp_status']),
            'rsvp_status' => $request->enum('rsvp_status', RsvpStatus::class) ?? $guest->rsvp_status,
        ]);

        return redirect()
            ->route('events.show', $event)
            ->with('status', __('Guest updated.'));
    }

    public function destroy(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $guest);

        $guest->delete();

        return redirect()
            ->route('events.show', $event)
            ->with('status', __('Guest removed.'));
    }

    public function import(ImportGuestsRequest $request, Event $event, ImportGuests $importGuests): RedirectResponse
    {
        $count = $importGuests->handle($event, $request->file('file'));

        return redirect()
            ->route('events.show', $event)
            ->with('status', trans_choice('Imported :count guest.|Imported :count guests.', $count, ['count' => $count]));
    }
}
