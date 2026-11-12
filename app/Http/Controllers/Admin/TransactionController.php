<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\WalletEntryType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $type = WalletEntryType::tryFrom((string) $request->query('type', ''));
        $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->string('q')->trim()->toString());

        $filtered = WalletTransaction::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            });

        return view('admin.transactions.index', [
            'transactions' => (clone $filtered)
                ->with(['user:id,name', 'event:id,title', 'recorder:id,name'])
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'totals' => [
                'in' => (int) (clone $filtered)->where('amount', '>', 0)->sum('amount'),
                'out' => (int) -(clone $filtered)->where('amount', '<', 0)->sum('amount'),
                'revenue' => (int) -WalletTransaction::query()
                    ->whereIn('type', WalletEntryType::revenueTypes())
                    ->sum('amount'),
            ],
            'type' => $type,
        ]);
    }
}
