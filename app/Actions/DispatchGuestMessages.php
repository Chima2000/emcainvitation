<?php

namespace App\Actions;

use App\Jobs\SendGuestSms;
use App\MessageChannel;
use App\MessagePurpose;
use App\MessageStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\ServiceRate;
use App\Models\User;
use App\RsvpStatus;
use App\ServiceItem;
use App\Support\SystemSettings;
use App\WalletEntryType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DispatchGuestMessages
{
    public function __construct(private RecordWalletEntry $recordWalletEntry) {}

    /**
     * Prepare messages for every waiting guest. Package allowance is used first, then the wallet pays
     * per message, and preparing stops when the wallet cannot cover the next guest. SMS messages are
     * queued for the gateway when it is set up; everything else is delivered by hand.
     *
     * @return array{prepared: int, paused: int, charged: int, covered: int, queued: int, unreachable: int}
     */
    public function handle(Event $event, MessagePurpose $purpose, MessageChannel $channel): array
    {
        $sendsThroughGateway = self::sendsThroughGateway($channel);

        return DB::transaction(function () use ($event, $purpose, $channel, $sendsThroughGateway): array {
            $host = User::query()->lockForUpdate()->findOrFail($event->user_id);
            $guests = $this->recipients($event, $purpose);
            $unitPrice = $this->unitPrice($purpose, $channel);
            $allowance = $event->packageAllowanceLeft($purpose);
            $available = $host->wallet_balance;
            $charged = 0;
            $covered = 0;
            $prepared = 0;
            $unreachable = 0;

            foreach ($guests as $guest) {
                if ($sendsThroughGateway && $guest->internationalPhone() === null) {
                    $unreachable++;

                    continue;
                }

                $isCovered = $allowance > 0;
                $cost = $isCovered ? 0 : $unitPrice;

                if ($cost > $available - $charged) {
                    break;
                }

                $guest->setRelation('event', $event);

                $message = $event->messages()->create([
                    'guest_id' => $guest->id,
                    'channel' => $channel,
                    'purpose' => $purpose,
                    'body' => $this->body($event, $guest, $purpose),
                    'status' => $sendsThroughGateway ? MessageStatus::Queued : MessageStatus::Sent,
                    'cost' => $cost,
                    'covered_by_package' => $isCovered,
                    'sent_at' => $sendsThroughGateway ? null : now(),
                ]);

                if ($sendsThroughGateway) {
                    SendGuestSms::dispatch($message->setRelation('guest', $guest));
                }

                if ($purpose === MessagePurpose::Invitation) {
                    $guest->sent_at = now();
                    $guest->save();
                }

                if ($isCovered) {
                    $allowance--;
                    $covered++;
                }

                $charged += $cost;
                $prepared++;
            }

            if ($charged > 0) {
                $this->recordWalletEntry->handle(
                    $host,
                    WalletEntryType::MessageCharge,
                    -$charged,
                    trans_choice(':count :purpose message by :channel for :event|:count :purpose messages by :channel for :event', $prepared - $covered, [
                        'count' => $prepared - $covered,
                        'purpose' => mb_strtolower($purpose->label()),
                        'channel' => $channel->label(),
                        'event' => $event->title,
                    ]),
                    ['event_id' => $event->id],
                );
            }

            return [
                'prepared' => $prepared,
                'paused' => $guests->count() - $prepared - $unreachable,
                'charged' => $charged,
                'covered' => $covered,
                'queued' => $sendsThroughGateway ? $prepared : 0,
                'unreachable' => $unreachable,
            ];
        });
    }

    /**
     * SMS goes out automatically through the gateway once the admin has set it up.
     */
    public static function sendsThroughGateway(MessageChannel $channel): bool
    {
        return $channel === MessageChannel::Sms && SystemSettings::smsIsConfigured();
    }

    public function unitPrice(MessagePurpose $purpose, MessageChannel $channel): int
    {
        $rates = ServiceRate::table();

        return match ($purpose) {
            MessagePurpose::Invitation => $rates[ServiceItem::Card->value] + $rates[ServiceItem::forChannel($channel)->value],
            MessagePurpose::Reminder => $rates[ServiceItem::Reminder->value],
            MessagePurpose::Thanks => $rates[ServiceItem::ThankYou->value],
        };
    }

    /**
     * @return Collection<int, Guest>
     */
    private function recipients(Event $event, MessagePurpose $purpose): Collection
    {
        $query = $event->guests()->orderBy('id');

        return match ($purpose) {
            MessagePurpose::Invitation => $query->whereNull('sent_at')->get(),
            MessagePurpose::Reminder => $query
                ->where('rsvp_status', RsvpStatus::Pending)
                ->whereDoesntHave('messages', function ($messages): void {
                    $messages
                        ->where('purpose', MessagePurpose::Reminder)
                        ->where('status', '!=', MessageStatus::Failed)
                        ->whereDate('created_at', today());
                })
                ->get(),
            MessagePurpose::Thanks => $query
                ->whereNotNull('checked_in_at')
                ->whereDoesntHave('messages', function ($messages): void {
                    $messages
                        ->where('purpose', MessagePurpose::Thanks)
                        ->where('status', '!=', MessageStatus::Failed);
                })
                ->get(),
        };
    }

    private function body(Event $event, Guest $guest, MessagePurpose $purpose): string
    {
        if ($purpose === MessagePurpose::Thanks) {
            return __('Thank you, :name. :hosts appreciated you being at :title.', [
                'name' => $guest->name,
                'hosts' => $event->hostsLabel(),
                'title' => $event->title,
            ]);
        }

        return $guest->invitationMessage();
    }
}
