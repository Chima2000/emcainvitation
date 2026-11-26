<?php

namespace App\Actions;

use App\Models\User;
use App\Support\ChosenInvitationTemplate;
use App\Support\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContinueHostRegistration
{
    public const string PendingSignIn = 'pending_host_sign_in';

    public function begin(Request $request): RedirectResponse
    {
        $request->session()->put(self::PendingSignIn, true);

        $user = $request->user();

        if ($user instanceof User && ! $user->hasCompletedProfile()) {
            return redirect()
                ->route('profile.edit')
                ->with('status', __('You are now a host. Complete your location details, then choose a subscription.'));
        }

        return $this->afterProfile($request);
    }

    public function afterProfile(Request $request): RedirectResponse
    {
        if (SystemSettings::subscriptionRequired()) {
            return redirect()
                ->route('subscription.index')
                ->with('status', __('You are now a host. Choose a subscription, then sign in.'));
        }

        return $this->signIn($request);
    }

    public function signIn(Request $request, ?string $status = null): RedirectResponse
    {
        $templateId = (int) $request->session()->get(ChosenInvitationTemplate::SessionKey);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login', array_filter([
                'template' => $templateId > 0 ? $templateId : null,
            ]))
            ->with('status', $status ?? __('You are now a host. Sign in with the phone number and password you just chose.'));
    }
}
