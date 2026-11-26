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
use App\PledgeStatus;
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
    public function handle(Event $event, MessagePurpose $purpose, MessageChannel $channel, string $audience = 'waiting', ?int $groupId = null): array
    {
        $sendsThroughGateway = self::sendsThroughGateway($channel);

        return DB::transaction(function () use ($event, $purpose, $channel, $sendsThroughGateway, $audience, $groupId): array {
            $host = User::query()->lockForUpdate()->findOrFail($event->user_id);
            $guests = $this->recipients($event, $purpose, $audience, $groupId);
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
            MessagePurpose::Invitation => $channel === MessageChannel::Email
                ? 0
                : $rates[ServiceItem::Card->value] + $rates[ServiceItem::forChannel($channel)->value],
            MessagePurpose::Reminder, MessagePurpose::Pledge => $rates[ServiceItem::Reminder->value],
            MessagePurpose::Thanks => $rates[ServiceItem::ThankYou->value],
            MessagePurpose::Trial => 0,
        };
    }

    /**
     * What a batch would cost, without preparing a message or moving money.
     *
     * @return array{count: int, unreachable: int, unit: int, covered: int, charged: int, balance: int, shortfall: int, guest: ?Guest, body: ?string}
     */
    public function quote(Event $event, MessagePurpose $purpose, MessageChannel $channel, string $audience = 'waiting', ?int $groupId = null): array
    {
        $event->loadMissing('user');
        $guests = $this->recipients($event, $purpose, $audience, $groupId);
        $unitPrice = $this->unitPrice($purpose, $channel);
        $allowance = $event->packageAllowanceLeft($purpose);
        $sendsThroughGateway = self::sendsThroughGateway($channel);
        $covered = 0;
        $charged = 0;
        $reachable = 0;
        $unreachable = 0;
        $preview = null;

        foreach ($guests as $guest) {
            if ($sendsThroughGateway && $guest->internationalPhone() === null) {
                $unreachable++;

                continue;
            }

            $reachable++;
            $preview ??= $guest;

            if ($allowance > 0) {
                $allowance--;
                $covered++;

                continue;
            }

            $charged += $unitPrice;
        }

        $balance = (int) ($event->user?->wallet_balance ?? 0);

        return [
            'count' => $reachable,
            'unreachable' => $unreachable,
            'unit' => $unitPrice,
            'covered' => $covered,
            'charged' => $charged,
            'balance' => $balance,
            'shortfall' => max(0, $charged - $balance),
            'guest' => $preview,
            'body' => $preview instanceof Guest ? $this->body($event, $preview, $purpose) : null,
        ];
    }

    /**
     * @return Collection<int, Guest>
     */
    private function recipients(Event $event, MessagePurpose $purpose, string $audience = 'waiting', ?int $groupId = null): Collection
    {
        $query = $event->guests()->orderBy('id');

        if ($audience === 'unreplied') {
            $query->where('rsvp_status', RsvpStatus::Pending);
        }

        if ($audience === 'group') {
            $memberIds = $event->guestGroups()->whereKey($groupId)->first()
                ?->guests()
                ->pluck('guests.id')
                ->all() ?? [];
            $query->whereIn('id', $memberIds);
        }

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
            MessagePurpose::Pledge => $query
                ->whereHas('pledges', function ($pledges): void {
                    $pledges->where('status', PledgeStatus::Pending);
                })
                ->whereDoesntHave('messages', function ($messages): void {
                    $messages
                        ->where('purpose', MessagePurpose::Pledge)
                        ->where('status', '!=', MessageStatus::Failed)
                        ->whereDate('created_at', today());
                })
                ->with(['pledges' => function ($pledges): void {
                    $pledges->where('status', PledgeStatus::Pending);
                }])
                ->get(),
            MessagePurpose::Trial => new Collection,
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

        if ($purpose === MessagePurpose::Pledge) {
            $guest->loadMissing(['pledges' => function ($pledges): void {
                $pledges->where('status', PledgeStatus::Pending);
            }]);

            return __('Hello :name, your open pledge for :title is TZS :amount.', [
                'name' => $guest->name,
                'title' => $event->title,
                'amount' => number_format((int) $guest->pledges->sum('amount')),
            ]);
        }

        return $guest->invitationMessage();
    }
}
