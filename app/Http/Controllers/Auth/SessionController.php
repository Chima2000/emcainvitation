<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\ChosenInvitationTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public const string OpenTemplatesAfterAuth = 'open_templates_after_auth';

    public function create(Request $request, ChosenInvitationTemplate $chosenInvitationTemplate): View
    {
        $chosenInvitationTemplate->rememberFromRequest($request);

        if ($request->query('next') === 'templates') {
            $request->session()->put(self::OpenTemplatesAfterAuth, true);
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request, ChosenInvitationTemplate $chosenInvitationTemplate): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            $chosenInvitationTemplate->forget($request);

            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user?->isLead() || $user?->hasCompletedProfile()) {
            if ($request->session()->pull(self::OpenTemplatesAfterAuth)) {
                return redirect()->route('template-library.index');
            }

            return redirect()->route('dashboard');
        }

        return redirect()->route('profile.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
