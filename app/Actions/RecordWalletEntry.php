<?php

namespace App\Actions;

use App\Models\User;
use App\Models\WalletTransaction;
use App\WalletEntryType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordWalletEntry
{
    /**
     * Move money in or out of a host wallet and write the matching statement line.
     *
     * @param  array{event_id?: int|null, top_up_request_id?: int|null, recorded_by?: int|null}  $links
     */
    public function handle(User $user, WalletEntryType $type, int $amount, string $description, array $links = []): WalletTransaction
    {
        return DB::transaction(function () use ($user, $type, $amount, $description, $links): WalletTransaction {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $balance = $lockedUser->wallet_balance + $amount;

            if ($balance < 0) {
                throw ValidationException::withMessages([
                    'wallet' => __('The wallet balance is TZS :balance. Top up at least TZS :needed to continue.', [
                        'balance' => number_format($lockedUser->wallet_balance),
                        'needed' => number_format(abs($balance)),
                    ]),
                ]);
            }

            $lockedUser->forceFill(['wallet_balance' => $balance])->save();
            $user->setAttribute('wallet_balance', $balance);

            return $lockedUser->walletTransactions()->create([
                ...$links,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balance,
                'description' => $description,
            ]);
        });
    }
}
