<?php

namespace App\Actions;

use App\Models\TopUpRequest;
use App\Models\User;
use App\TopUpStatus;
use App\WalletEntryType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReviewTopUp
{
    public function __construct(
        private RecordWalletEntry $recordWalletEntry,
        private PurchaseSubscription $purchaseSubscription,
    ) {}

    /**
     * Confirm the payment reached EmCa and credit the host wallet once. A payment sent for a
     * subscription then pays for it; if that fails, the money simply stays in the wallet.
     */
    public function approve(TopUpRequest $topUp, User $reviewer, ?string $note = null): TopUpRequest
    {
        return DB::transaction(function () use ($topUp, $reviewer, $note): TopUpRequest {
            $lockedTopUp = $this->lockPending($topUp);

            $this->recordWalletEntry->handle(
                $lockedTopUp->user,
                WalletEntryType::TopUp,
                $lockedTopUp->amount,
                __('Top-up by :method, reference :reference', [
                    'method' => $lockedTopUp->method->label(),
                    'reference' => $lockedTopUp->reference,
                ]),
                [
                    'top_up_request_id' => $lockedTopUp->id,
                    'recorded_by' => $reviewer->id,
                ],
            );

            if ($lockedTopUp->subscription_period !== null) {
                try {
                    $this->purchaseSubscription->handle($lockedTopUp->user, $lockedTopUp->subscription_period);
                } catch (ValidationException) {
                    Log::warning('A subscription top-up was approved but the subscription could not be activated.', [
                        'top_up_request_id' => $lockedTopUp->id,
                    ]);
                }
            }

            return $this->close($lockedTopUp, TopUpStatus::Approved, $reviewer, $note);
        });
    }

    public function reject(TopUpRequest $topUp, User $reviewer, string $note): TopUpRequest
    {
        return DB::transaction(function () use ($topUp, $reviewer, $note): TopUpRequest {
            return $this->close($this->lockPending($topUp), TopUpStatus::Rejected, $reviewer, $note);
        });
    }

    private function lockPending(TopUpRequest $topUp): TopUpRequest
    {
        $lockedTopUp = TopUpRequest::query()->with('user')->lockForUpdate()->findOrFail($topUp->id);

        if (! $lockedTopUp->isPending()) {
            throw ValidationException::withMessages([
                'top_up' => __('This top-up was already reviewed.'),
            ]);
        }

        return $lockedTopUp;
    }

    private function close(TopUpRequest $topUp, TopUpStatus $status, User $reviewer, ?string $note): TopUpRequest
    {
        $topUp->forceFill([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();

        return $topUp;
    }
}
