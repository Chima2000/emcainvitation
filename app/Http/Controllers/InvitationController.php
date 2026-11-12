<?php

namespace App\Http\Controllers;

use App\Http\Requests\RsvpRequest;
use App\Models\Guest;
use App\RsvpStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $guest = $this->guest($token);
        $guest->load('event.template');

        return view('invitations.show', [
            'guest' => $guest,
            'event' => $guest->event,
        ]);
    }

    public function rsvp(RsvpRequest $request, string $token): RedirectResponse
    {
        $guest = $this->guest($token);
        $status = $request->enum('rsvp_status', RsvpStatus::class);

        $guest->rsvp_status = $status;

        if ($status === RsvpStatus::Attending) {
            $guest->party_size = $request->integer('party_size');
        }

        $guest->save();

        return redirect()
            ->route('invitations.show', $guest->token)
            ->with('status', __('Your reply has been saved. The host can see it now.'));
    }

    private function guest(string $token): Guest
    {
        return Guest::query()->where('token', $token)->firstOrFail();
    }
}
