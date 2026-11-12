<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SystemSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionIsActive
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user instanceof User
            && ! $user->isSuperAdmin()
            && ! $user->hasActiveSubscription()
            && SystemSettings::subscriptionRequired()
        ) {
            return redirect()
                ->route('subscription.index')
                ->with('status', __('Your account is inactive. Choose a subscription to keep using your dashboard.'));
        }

        return $next($request);
    }
}
