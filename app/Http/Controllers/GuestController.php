<?php

namespace App\Http\Controllers;

use App\Actions\ImportGuests;
use App\Actions\IssueGuestPass;
use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\ImportGuestsRequest;
use App\Http\Requests\StoreGuestRequest;
use App\Http\Requests\UpdateGuestRequest;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Guest;
use App\RsvpStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestController extends Controller
{
    use ManagesOwnedEvent;

    public function store(StoreGuestRequest $request, Event $event, IssueGuestPass $issueGuestPass): RedirectResponse
    {
        $guest = $issueGuestPass->handle($event, [
            ...$request->safe()->except(['rsvp_status']),
            'rsvp_status' => $request->enum('rsvp_status', RsvpStatus::class) ?? RsvpStatus::Pending,
        ]);

        Contact::remember(
            $request->user(),
            $guest->name,
            $guest->phone,
            $guest->email,
            $guest->gender?->value,
        );

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
        $result = $importGuests->handle($event, $request->file('file'));

        return redirect()
            ->route('events.show', $event)
            ->with('status', $this->importStatus($result['imported'], $result['skipped'], $result['stopped_at_trial_limit']));
    }

    public function template(Request $request, Event $event): StreamedResponse
    {
        $this->ownedEvent($request, $event);

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ImportGuests::Columns);
            fputcsv($handle, ['Asha Mushi', '0712345678', 'asha@example.com', 'female', '2', 'Family', 'High table']);
            fclose($handle);
        }, 'guests-template.csv', ['Content-Type' => 'text/csv']);
    }

    private function importStatus(int $imported, int $skipped, bool $stoppedAtTrialLimit): string
    {
        if ($stoppedAtTrialLimit) {
            return trans_choice(
                'Imported :count guest. A trial stops at :limit, so the rest were left out.|Imported :count guests. A trial stops at :limit, so the rest were left out.',
                $imported,
                ['count' => $imported, 'limit' => Event::TrialGuestLimit],
            );
        }

        if ($imported === 0) {
            return __('Those guests are already on this event.');
        }

        $message = trans_choice('Imported :count guest.|Imported :count guests.', $imported, ['count' => $imported]);

        if ($skipped === 0) {
            return $message;
        }

        return $message.' '.__(':count already on the list were left out.', ['count' => $skipped]);
    }
}
