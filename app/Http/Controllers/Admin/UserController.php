<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHostRequest;
use App\Http\Requests\Admin\UpdateHostRequest;
use App\Models\User;
use App\Support\TanzaniaLocations;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->string('q')->trim()->toString());
        $role = UserRole::tryFrom((string) $request->query('role', ''));

        return view('admin.users.index', [
            'users' => User::query()
                ->withCount('events')
                ->when($role, fn ($query) => $query->where('role', $role))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('region', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'totals' => [
                'hosts' => User::query()->where('role', UserRole::Host)->count(),
                'with_balance' => User::query()->where('role', UserRole::Host)->where('wallet_balance', '>', 0)->count(),
                'pending_profiles' => User::query()->where('role', UserRole::Host)->whereNull('profile_completed_at')->count(),
                'wallet_float' => (int) User::query()->where('role', UserRole::Host)->sum('wallet_balance'),
            ],
            'role' => $role,
        ]);
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        $user->loadCount('events');

        return view('admin.users.show', [
            'user' => $user,
            'events' => $user->events()->with('package:id,name')->withCount('guests')->latest('event_date')->limit(10)->get(),
            'transactions' => $user->walletTransactions()->with(['event:id,title', 'recorder:id,name'])->latest()->paginate(10, ['*'], 'statement')->withQueryString(),
            'topUps' => $user->topUpRequests()->latest()->limit(8)->get(),
            'spent' => (int) -$user->walletTransactions()->where('amount', '<', 0)->sum('amount'),
            'toppedUp' => (int) $user->walletTransactions()->where('amount', '>', 0)->sum('amount'),
        ]);
    }

    public function store(StoreHostRequest $request): RedirectResponse
    {
        $validated = $request->safe()->only([
            'first_name',
            'last_name',
            'phone',
            'email',
            'password',
        ]);

        $location = TanzaniaLocations::normalize($request->validated());

        User::query()->create([
            ...$validated,
            ...$location,
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'role' => UserRole::Host,
            'profile_completed_at' => $location === [] ? null : now(),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('Host account saved.'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    public function update(UpdateHostRequest $request, User $user): RedirectResponse
    {
        $validated = $request->safe()->only([
            'first_name',
            'last_name',
            'phone',
            'email',
        ]);

        $attributes = [
            ...$validated,
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
        ];

        $location = TanzaniaLocations::normalize($request->validated());

        if ($location !== []) {
            $attributes = [
                ...$attributes,
                ...$location,
                'profile_completed_at' => now(),
            ];
        }

        if ($request->filled('password')) {
            $attributes['password'] = $request->validated('password');
        }

        $user->update($attributes);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('Host updated.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('Host removed.'));
    }
}
