<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordWalletEntry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustWalletRequest;
use App\Models\User;
use App\WalletEntryType;
use Illuminate\Http\RedirectResponse;

class WalletAdjustmentController extends Controller
{
    public function store(AdjustWalletRequest $request, User $user, RecordWalletEntry $recordWalletEntry): RedirectResponse
    {
        abort_if($user->isSuperAdmin(), 404);

        $recordWalletEntry->handle(
            $user,
            WalletEntryType::Adjustment,
            $request->signedAmount(),
            $request->string('reason')->toString(),
            ['recorded_by' => $request->user()->id],
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', __('Wallet updated. New balance: TZS :balance.', ['balance' => number_format($user->wallet_balance)]));
    }
}
