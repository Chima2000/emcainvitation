<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ReviewTopUp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectTopUpRequest;
use App\Models\TopUpRequest;
use App\PaymentMethod;
use App\TopUpStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TopUpController extends Controller
{
    public function index(Request $request): View
    {
        $status = TopUpStatus::tryFrom((string) $request->query('status', ''));
        $method = PaymentMethod::tryFrom((string) $request->query('method', ''));
        $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->string('q')->trim()->toString());

        return view('admin.top-ups.index', [
            'topUps' => TopUpRequest::query()
                ->with(['user:id,name,phone,wallet_balance', 'reviewer:id,name'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($method, fn ($query) => $query->where('method', $method))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->where('reference', 'like', "%{$search}%")
                            ->orWhere('payer_phone', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                    });
                })
                ->orderByRaw('case when status = ? then 0 else 1 end', [TopUpStatus::Pending->value])
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'counts' => TopUpRequest::query()
                ->selectRaw('status, count(*) as total, coalesce(sum(amount), 0) as amount')
                ->groupBy('status')
                ->get()
                ->mapWithKeys(fn (TopUpRequest $row): array => [
                    $row->status->value => ['total' => (int) $row->getAttribute('total'), 'amount' => (int) $row->getAttribute('amount')],
                ]),
            'status' => $status,
            'method' => $method,
        ]);
    }

    public function approve(Request $request, TopUpRequest $topUp, ReviewTopUp $reviewTopUp): RedirectResponse
    {
        $request->validate(['review_note' => ['nullable', 'string', 'max:255']]);

        $reviewTopUp->approve($topUp, $request->user(), $request->string('review_note')->toString() ?: null);

        return back()->with('status', __('Top-up confirmed. TZS :amount was added to :name\'s wallet.', [
            'amount' => number_format($topUp->amount),
            'name' => $topUp->user->name,
        ]));
    }

    public function reject(RejectTopUpRequest $request, TopUpRequest $topUp, ReviewTopUp $reviewTopUp): RedirectResponse
    {
        $reviewTopUp->reject($topUp, $request->user(), $request->string('review_note')->toString());

        return back()->with('status', __('Top-up declined. The host will see your note.'));
    }
}
