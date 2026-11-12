<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SendVerificationCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendPasswordResetCodeRequest;
use App\Models\User;
use App\Support\OneTimeCode;
use App\Support\SystemSettings;
use App\Support\TanzanianPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetController extends Controller
{
    public const string ResetPhone = 'password_reset_phone';

    public function store(SendPasswordResetCodeRequest $request, SendVerificationCode $sendVerificationCode): RedirectResponse
    {
        $phone = $request->phone();
        $user = User::query()->where('phone', $phone)->first();

        if (! $user instanceof User) {
            return $this->failed(__('No account uses this phone number.'));
        }

        $throttleKey = 'password-reset-code:'.$phone;

        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            $request->session()->put(self::ResetPhone, $phone);

            return redirect()->route('password.reset')->withErrors(['code' => __('Please wait :seconds seconds before asking for another code.', [
                'seconds' => RateLimiter::availableIn($throttleKey),
            ])]);
        }

        if (! SystemSettings::smsIsConfigured() || ! $sendVerificationCode->handle(SendVerificationCode::PasswordReset, $phone, $user->first_name ?: $user->name)) {
            return $this->failed(__('We could not send the SMS right now. Try again in a few minutes or contact support.'));
        }

        RateLimiter::hit($throttleKey, 60);
        $request->session()->put(self::ResetPhone, $phone);

        return redirect()
            ->route('password.reset')
            ->with('status', __('We sent a 6-digit code to :phone.', ['phone' => $this->maskedPhone($phone)]));
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get(self::ResetPhone);

        if (! is_string($phone)) {
            return redirect()->route('login');
        }

        return view('auth.reset-password', [
            'maskedPhone' => $this->maskedPhone($phone),
            'localPhone' => TanzanianPhone::toLocal($phone),
            'minutes' => SystemSettings::codeLifetimeMinutes(),
        ]);
    }

    public function update(ResetPasswordRequest $request, OneTimeCode $oneTimeCode): RedirectResponse
    {
        $phone = $request->session()->get(self::ResetPhone);

        if (! is_string($phone)) {
            return redirect()->route('login');
        }

        if (! $oneTimeCode->verify(SendVerificationCode::PasswordReset, $phone, $request->validated('code'))) {
            return back()->withErrors(['code' => __('That code is wrong or has expired. Check the SMS or ask for a new code.')]);
        }

        $user = User::query()->where('phone', $phone)->firstOrFail();
        $user->forceFill([
            'password' => $request->validated('password'),
            'phone_verified_at' => $user->phone_verified_at ?? now(),
        ])->setRememberToken(null);
        $user->save();

        $request->session()->forget(self::ResetPhone);

        return redirect()
            ->route('login')
            ->with('status', __('Your password was changed. Sign in with the new password.'));
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withInput()
            ->withErrors(['reset_phone' => $message]);
    }

    private function maskedPhone(string $phone): string
    {
        $local = TanzanianPhone::toLocal($phone);

        return substr($local, 0, 4).'***'.substr($local, -3);
    }
}
