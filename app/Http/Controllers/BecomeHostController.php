<?php

namespace App\Http\Controllers;

use App\Actions\ContinueHostRegistration;
use App\Actions\PromoteLeadToHost;
use App\Actions\SendVerificationCode;
use App\Http\Requests\Auth\VerifyPhoneRequest;
use App\Http\Requests\BecomeHostRequest;
use App\Models\User;
use App\PhoneInput;
use App\Support\OneTimeCode;
use App\Support\SystemSettings;
use App\Support\TanzanianPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class BecomeHostController extends Controller
{
    public const string PendingHostRegistration = 'pending_host_registration';

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isLead()) {
            return redirect()->route($user?->isSuperAdmin() ? 'admin.dashboard' : 'dashboard');
        }

        return view('hosts.register', [
            'user' => $user,
        ]);
    }

    public function store(BecomeHostRequest $request, SendVerificationCode $sendVerificationCode, PromoteLeadToHost $promoteLeadToHost, ContinueHostRegistration $continueHostRegistration): RedirectResponse
    {
        $details = [
            ...$request->safe()->only(['first_name', 'last_name', 'phone', 'email']),
            'password' => Hash::make($request->validated('password')),
        ];

        if (! SystemSettings::registrationRequiresPhoneCode()) {
            $promoteLeadToHost->handle($request->user(), $details);

            return $this->registered($request, $continueHostRegistration);
        }

        if (! $sendVerificationCode->handle(SendVerificationCode::Registration, $details['phone'], $details['first_name'])) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['phone' => __('We could not send the SMS code to this number. Check the number and try again.')]);
        }

        $request->session()->put(self::PendingHostRegistration, $details);

        return redirect()->route('become-host.verify');
    }

    public function verify(Request $request): View|RedirectResponse
    {
        $pending = $this->pending($request);
        $user = $request->user();

        if (! $user instanceof User || ! $user->isLead() || $pending === null) {
            return redirect()->route('become-host.create');
        }

        return view('hosts.verify', [
            'phone' => $pending['phone'],
            'minutes' => SystemSettings::codeLifetimeMinutes(),
        ]);
    }

    public function confirm(VerifyPhoneRequest $request, OneTimeCode $oneTimeCode, PromoteLeadToHost $promoteLeadToHost, ContinueHostRegistration $continueHostRegistration): RedirectResponse
    {
        $pending = $this->pending($request);
        $user = $request->user();

        if (! $user instanceof User || ! $user->isLead() || $pending === null) {
            return redirect()->route('become-host.create');
        }

        if (! $oneTimeCode->verify(SendVerificationCode::Registration, $pending['phone'], $request->validated('code'))) {
            return back()->withErrors(['code' => __('That code is wrong or has expired. Check the SMS or ask for a new code.')]);
        }

        $promoteLeadToHost->handle($user, $pending);

        return $this->registered($request, $continueHostRegistration);
    }

    public function resend(Request $request, SendVerificationCode $sendVerificationCode): RedirectResponse
    {
        $pending = $this->pending($request);

        if ($pending === null || ! $request->user()?->isLead()) {
            return redirect()->route('become-host.create');
        }

        $throttleKey = 'become-host-code:'.$pending['phone'];

        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            return back()->withErrors(['code' => __('Please wait :seconds seconds before asking for another code.', [
                'seconds' => RateLimiter::availableIn($throttleKey),
            ])]);
        }

        if (! $sendVerificationCode->handle(SendVerificationCode::Registration, $pending['phone'], $pending['first_name'])) {
            return back()->withErrors(['code' => __('We could not send the SMS code to this number. Check the number and try again.')]);
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->with('status', __('A new code is on its way.'));
    }

    private function registered(Request $request, ContinueHostRegistration $continueHostRegistration): RedirectResponse
    {
        $request->session()->forget(self::PendingHostRegistration);

        return $continueHostRegistration->begin($request);
    }

    /**
     * @return array{first_name: string, last_name: string, phone: string, email: string, password: string}|null
     */
    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get(self::PendingHostRegistration);

        if (! is_array($pending) || ! isset($pending['phone'], $pending['password'], $pending['first_name'], $pending['last_name'], $pending['email'])) {
            return null;
        }

        $pending['phone'] = TanzanianPhone::normalize((string) $pending['phone'], PhoneInput::Registration) ?? (string) $pending['phone'];

        return $pending;
    }
}
