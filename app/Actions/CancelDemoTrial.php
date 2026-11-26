<?php

namespace App\Actions;

use App\Models\DemoRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelDemoTrial
{
    /**
     * Stop this demo trial. Paid time that was already on the account is left in place.
     */
    public function handle(DemoRequest $demo): DemoRequest
    {
        if ($demo->trial_cancelled_at !== null || $demo->trial_ends_at === null || ! $demo->trial_ends_at->isFuture()) {
            throw ValidationException::withMessages([
                'trial' => __('There is no active trial to cancel.'),
            ]);
        }

        DB::transaction(function () use ($demo): void {
            $lockedDemo = DemoRequest::query()->lockForUpdate()->findOrFail($demo->id);
            $user = $lockedDemo->user_id === null
                ? null
                : User::query()->lockForUpdate()->find($lockedDemo->user_id);

            if ($user instanceof User && $this->subscriptionEndsWithThisTrial($user, $lockedDemo)) {
                $restoredEnd = $lockedDemo->trial_starts_at !== null && $lockedDemo->trial_starts_at->isFuture()
                    ? $lockedDemo->trial_starts_at
                    : now();

                $user->forceFill(['subscription_ends_at' => $restoredEnd])->save();
            }

            $lockedDemo->forceFill(['trial_cancelled_at' => now()])->save();
        });

        return $demo->refresh();
    }

    private function subscriptionEndsWithThisTrial(User $user, DemoRequest $demo): bool
    {
        if ($user->subscription_ends_at === null || $demo->trial_ends_at === null) {
            return false;
        }

        return abs($user->subscription_ends_at->diffInSeconds($demo->trial_ends_at)) < 2;
    }
}
