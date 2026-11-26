<?php

namespace App\Http\Controllers;

use App\Actions\DispatchGuestMessages;
use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\MessageChannel;
use App\MessagePurpose;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OutboundMessageController extends Controller
{
    use ManagesOwnedEvent;

    public function invitations(Request $request, Event $event, DispatchGuestMessages $dispatchGuestMessages): RedirectResponse
    {
        return $this->dispatch($request, $event, $dispatchGuestMessages, MessagePurpose::Invitation);
    }

    public function reminders(Request $request, Event $event, DispatchGuestMessages $dispatchGuestMessages): RedirectResponse
    {
        return $this->dispatch($request, $event, $dispatchGuestMessages, MessagePurpose::Reminder);
    }

    public function thanks(Request $request, Event $event, DispatchGuestMessages $dispatchGuestMessages): RedirectResponse
    {
        return $this->dispatch($request, $event, $dispatchGuestMessages, MessagePurpose::Thanks);
    }

    public function pledges(Request $request, Event $event, DispatchGuestMessages $dispatchGuestMessages): RedirectResponse
    {
        return $this->dispatch($request, $event, $dispatchGuestMessages, MessagePurpose::Pledge);
    }

    public function preview(Request $request, Event $event, DispatchGuestMessages $dispatchGuestMessages): View
    {
        $this->ownedEvent($request, $event);
        $purpose = MessagePurpose::tryFrom((string) $request->input('purpose')) ?? MessagePurpose::Invitation;

        abort_if($purpose === MessagePurpose::Trial, 404);

        $channel = MessageChannel::tryFrom((string) $request->input('channel')) ?? MessageChannel::Whatsapp;
        abort_unless($channel->isEventChannel(), 404);

        [$audience, $groupId] = $this->audience($request);
        $quote = $dispatchGuestMessages->quote($event, $purpose, $channel, $audience, $groupId);

        return view('events.messages.preview', [
            'event' => $event,
            'purpose' => $purpose,
            'channel' => $channel,
            'audience' => $audience,
            'groupId' => $groupId,
            'group' => $groupId ? $event->guestGroups()->find($groupId) : null,
            'quote' => $quote,
        ]);
    }

    private function dispatch(
        Request $request,
        Event $event,
        DispatchGuestMessages $dispatchGuestMessages,
        MessagePurpose $purpose,
    ): RedirectResponse {
        $this->ownedEvent($request, $event);

        $channel = MessageChannel::tryFrom((string) $request->input('channel')) ?? MessageChannel::Whatsapp;
        abort_unless($channel->isEventChannel(), 404);

        [$audience, $groupId] = $this->audience($request);

        if ($request->boolean('reviewed')) {
            $quote = $dispatchGuestMessages->quote($event, $purpose, $channel, $audience, $groupId);

            if ($quote['shortfall'] > 0) {
                return redirect()
                    ->route('events.messages.preview', [
                        'event' => $event,
                        'purpose' => $purpose->value,
                        'channel' => $channel->value,
                        'audience' => $audience,
                        'group_id' => $groupId,
                    ])
                    ->with('error', __('This batch needs TZS :amount more in the wallet before it can start.', [
                        'amount' => number_format($quote['shortfall']),
                    ]));
            }
        }

        $result = $dispatchGuestMessages->handle($event, $purpose, $channel, $audience, $groupId);

        $redirect = redirect()
            ->route('events.show', $event)
            ->withFragment('messages');

        $unreachableNote = $result['unreachable'] > 0
            ? ' '.trans_choice(':count guest has no phone number and was skipped.|:count guests have no phone number and were skipped.', $result['unreachable'], [
                'count' => $result['unreachable'],
            ])
            : '';

        if ($result['prepared'] === 0 && $result['paused'] === 0) {
            return $redirect->with('status', __('No :purpose messages were waiting to be prepared.', [
                'purpose' => mb_strtolower($purpose->label()),
            ]).$unreachableNote);
        }

        $summary = trans_choice('Prepared :count :purpose message.|Prepared :count :purpose messages.', $result['prepared'], [
            'count' => $result['prepared'],
            'purpose' => mb_strtolower($purpose->label()),
        ]);

        if ($result['covered'] > 0) {
            $summary .= ' '.__(':count covered by your package.', ['count' => $result['covered']]);
        }

        if ($result['charged'] > 0) {
            $summary .= ' '.__('TZS :amount was taken from your wallet.', ['amount' => number_format($result['charged'])]);
        }

        if ($result['queued'] > 0) {
            $summary .= ' '.__('The SMS are on their way and usually arrive within a minute. Their status updates below.');
        } elseif ($result['prepared'] > 0) {
            $summary .= ' '.__('Open WhatsApp or SMS beside each guest to deliver them.');
        }

        $summary .= $unreachableNote;

        if ($result['paused'] > 0) {
            return $redirect
                ->with($result['prepared'] > 0 ? 'status' : 'error', $summary)
                ->with('wallet_paused', trans_choice(':count guest is waiting because the wallet balance is too low. Top up to continue.|:count guests are waiting because the wallet balance is too low. Top up to continue.', $result['paused'], [
                    'count' => $result['paused'],
                ]));
        }

        return $redirect->with('status', $summary);
    }

    /**
     * @return array{0: string, 1: ?int}
     */
    private function audience(Request $request): array
    {
        $audience = (string) $request->input('audience', 'waiting');

        if (! in_array($audience, ['waiting', 'unreplied', 'group'], true)) {
            $audience = 'waiting';
        }

        $groupId = $request->filled('group_id') ? $request->integer('group_id') : null;

        return [$audience, $audience === 'group' ? $groupId : null];
    }
}
