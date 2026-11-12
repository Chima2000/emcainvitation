<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTopUpRequest;
use App\Models\ServiceRate;
use App\PaymentMethod;
use App\ServiceItem;
use App\TopUpStatus;
use App\WalletEntryType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('wallet.index', [
            'balance' => $user->wallet_balance,
            'methods' => PaymentMethod::cases(),
            'topUps' => $user->topUpRequests()->latest()->limit(6)->get(),
            'pendingAmount' => (int) $user->topUpRequests()->where('status', TopUpStatus::Pending)->whereNull('subscription_period')->sum('amount'),
            'transactions' => $user->walletTransactions()->with('event:id,title')->latest()->paginate(15)->withQueryString(),
            'spentThisMonth' => (int) -$user->walletTransactions()
                ->whereIn('type', WalletEntryType::revenueTypes())
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'rates' => ServiceRate::table(),
            'items' => ServiceItem::cases(),
            'minimumTopUp' => (int) config('emca.wallet.minimum_top_up'),
            'maximumTopUp' => (int) config('emca.wallet.maximum_top_up'),
        ]);
    }

    public function store(StoreTopUpRequest $request): RedirectResponse
    {
        $request->user()->topUpRequests()->create($request->safe()->only([
            'amount',
            'method',
            'reference',
            'payer_phone',
            'note',
        ]));

        return redirect()
            ->route('wallet.index')
            ->with('status', __('Thanks! We received your top-up of TZS :amount. It shows in your wallet as soon as we confirm the payment.', [
                'amount' => number_format($request->integer('amount')),
            ]));
    }
}
