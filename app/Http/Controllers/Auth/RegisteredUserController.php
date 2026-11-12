<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterHost;
use App\Actions\SendVerificationCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Support\SystemSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    public const string PendingRegistration = 'pending_registration';

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterUserRequest $request, RegisterHost $registerHost, SendVerificationCode $sendVerificationCode): RedirectResponse
    {
        $details = [
            ...$request->safe()->only(['first_name', 'last_name', 'phone', 'email']),
            'password' => Hash::make($request->validated('password')),
        ];

        if (! SystemSettings::registrationRequiresPhoneCode()) {
            Auth::login($registerHost->handle($details, phoneVerified: false));
            $request->session()->regenerate();

            return redirect()->route('profile.edit');
        }

        if (! $sendVerificationCode->handle(SendVerificationCode::Registration, $details['phone'], $details['first_name'])) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['phone' => __('We could not send the SMS code to this number. Check the number and try again.')]);
        }

        $request->session()->put(self::PendingRegistration, $details);

        return redirect()->route('register.verify');
    }
}
