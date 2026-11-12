<?php

namespace App\Http\Controllers;

use App\Actions\DispatchGuestMessages;
use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\MessageChannel;
use App\MessagePurpose;
use App\Models\Event;
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

    private function dispatch(
        Request $request,
        Event $event,
        DispatchGuestMessages $dispatchGuestMessages,
        MessagePurpose $purpose,
    ): RedirectResponse {
        $this->ownedEvent($request, $event);

        $channel = MessageChannel::tryFrom((string) $request->input('channel')) ?? MessageChannel::Whatsapp;
        $result = $dispatchGuestMessages->handle($event, $purpose, $channel);

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
}
