<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsFullHost
{
    /**
     * A trial lead may prepare an event. Sending, check-in, and payment stay with a host.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isLead()) {
            return redirect()
                ->route('become-host.create')
                ->with('status', __('A trial can prepare an event. Register to become a host before you do this.'));
        }

        return $next($request);
    }
}
