<?php

namespace App\Actions;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\SubscriptionPeriod;
use App\WalletEntryType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseSubscription
{
    public function __construct(private RecordWalletEntry $recordWalletEntry) {}

    /**
     * Pay for a subscription period from the wallet. A running subscription is extended from its end date.
     */
    public function handle(User $user, SubscriptionPeriod $period): Subscription
    {
        $plan = SubscriptionPlan::forPeriod($period);

        if (! $plan instanceof SubscriptionPlan) {
            throw ValidationException::withMessages([
                'period' => __('This subscription period is not available right now.'),
            ]);
        }

        return DB::transaction(function () use ($user, $period, $plan): Subscription {
            $transaction = $this->recordWalletEntry->handle(
                $user,
                WalletEntryType::Subscription,
                -$plan->price,
                __('Subscription for :period', ['period' => mb_strtolower($period->label())]),
            );

            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $startsAt = $lockedUser->hasActiveSubscription() ? $lockedUser->subscription_ends_at : now();
            $endsAt = $period->endsAfter($startsAt);

            $lockedUser->forceFill(['subscription_ends_at' => $endsAt])->save();
            $user->setAttribute('subscription_ends_at', $endsAt);

            return $lockedUser->subscriptions()->create([
                'period' => $period,
                'amount' => $plan->price,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'wallet_transaction_id' => $transaction->id,
            ]);
        });
    }
}
