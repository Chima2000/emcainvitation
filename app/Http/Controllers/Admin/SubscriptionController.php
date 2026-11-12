<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSubscriptionPlansRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\SystemSettings;
use App\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        $hosts = User::query()->where('role', UserRole::Host);

        return view('admin.subscriptions.index', [
            'plans' => SubscriptionPlan::ordered(),
            'required' => SystemSettings::subscriptionRequired(),
            'activeHosts' => (clone $hosts)->where('subscription_ends_at', '>', now())->count(),
            'inactiveHosts' => (clone $hosts)->where(fn ($query) => $query->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', now()))->count(),
            'collectedThisMonth' => (int) Subscription::query()->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
            'subscriptions' => Subscription::query()->with('user:id,name,phone')->latest()->paginate(20),
        ]);
    }

    public function update(UpdateSubscriptionPlansRequest $request): RedirectResponse
    {
        foreach (SubscriptionPlan::ordered() as $plan) {
            $plan->update([
                'price' => (int) $request->validated('plans.'.$plan->period->value.'.price'),
                'is_active' => $request->boolean('plans.'.$plan->period->value.'.is_active'),
            ]);
        }

        SystemSettings::setSubscriptionRequired($request->boolean('required'));

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('status', __('Subscription settings saved.'));
    }
}
