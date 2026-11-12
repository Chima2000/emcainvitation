<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterHost;
use App\Actions\SendVerificationCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyPhoneRequest;
use App\Models\User;
use App\Support\OneTimeCode;
use App\Support\SystemSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class PhoneVerificationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $pending = $this->pendingRegistration($request);

        if ($pending === null) {
            return redirect()->route('register');
        }

        return view('auth.verify-phone', [
            'phone' => $pending['phone'],
            'minutes' => SystemSettings::codeLifetimeMinutes(),
        ]);
    }

    public function store(VerifyPhoneRequest $request, OneTimeCode $oneTimeCode, RegisterHost $registerHost): RedirectResponse
    {
        $pending = $this->pendingRegistration($request);

        if ($pending === null) {
            return redirect()->route('register');
        }

        if (! $oneTimeCode->verify(SendVerificationCode::Registration, $pending['phone'], $request->validated('code'))) {
            return back()->withErrors(['code' => __('That code is wrong or has expired. Check the SMS or ask for a new code.')]);
        }

        if (User::query()->where('phone', $pending['phone'])->orWhere('email', $pending['email'])->exists()) {
            $request->session()->forget(RegisteredUserController::PendingRegistration);

            return redirect()->route('login')->withErrors(['phone' => __('An account with this phone number or email already exists. Sign in instead.')]);
        }

        $user = $registerHost->handle($pending, phoneVerified: true);

        $request->session()->forget(RegisteredUserController::PendingRegistration);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('profile.edit')
            ->with('status', __('Your phone number is verified and your account is active.'));
    }

    public function resend(Request $request, SendVerificationCode $sendVerificationCode): RedirectResponse
    {
        $pending = $this->pendingRegistration($request);

        if ($pending === null) {
            return redirect()->route('register');
        }

        $throttleKey = 'register-code:'.$pending['phone'];

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

    /**
     * @return array{first_name: string, last_name: string, phone: string, email: string, password: string}|null
     */
    private function pendingRegistration(Request $request): ?array
    {
        $pending = $request->session()->get(RegisteredUserController::PendingRegistration);

        return is_array($pending) ? $pending : null;
    }
}
