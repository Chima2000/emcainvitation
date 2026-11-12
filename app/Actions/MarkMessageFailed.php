<?php

namespace App\Actions;

use App\MessagePurpose;
use App\MessageStatus;
use App\Models\OutboundMessage;
use App\WalletEntryType;
use Illuminate\Support\Facades\DB;

class MarkMessageFailed
{
    public function __construct(private RecordWalletEntry $recordWalletEntry) {}

    /**
     * Record that a queued message could not be delivered and give back what it cost: wallet money is refunded,
     * package credit is released, and an invitation guest becomes ready to be invited again.
     */
    public function handle(OutboundMessage $message, string $reason): void
    {
        DB::transaction(function () use ($message, $reason): void {
            $locked = OutboundMessage::query()->with(['event.user', 'guest'])->lockForUpdate()->find($message->id);

            if ($locked === null || $locked->status !== MessageStatus::Queued) {
                return;
            }

            $refund = $locked->cost;

            $locked->forceFill([
                'status' => MessageStatus::Failed,
                'failure_reason' => mb_strimwidth($reason, 0, 250, '...'),
                'cost' => 0,
                'covered_by_package' => false,
            ])->save();

            if ($locked->purpose === MessagePurpose::Invitation && $locked->guest !== null) {
                $locked->guest->forceFill(['sent_at' => null])->save();
            }

            if ($refund > 0) {
                $this->recordWalletEntry->handle(
                    $locked->event->user,
                    WalletEntryType::MessageCharge,
                    $refund,
                    __('Refund: the SMS to :name could not be delivered', ['name' => $locked->guest?->name ?? __('Guest')]),
                    ['event_id' => $locked->event_id],
                );
            }
        });
    }
}
