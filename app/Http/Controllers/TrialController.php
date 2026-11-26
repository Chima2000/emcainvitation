<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class TrialController extends Controller
{
    public function show(DemoRequest $demoRequest): View
    {
        abort_unless($demoRequest->trialIsActive(), 404);

        return view('trials.show', [
            'demo' => $demoRequest,
        ]);
    }

    public function store(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        abort_unless($demoRequest->must_set_password && $demoRequest->trialIsActive(), 404);

        $user = $demoRequest->user;

        abort_unless($user instanceof User, 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'phone_verified_at' => $user->phone_verified_at ?? now(),
        ])->save();
        $demoRequest->forceFill(['must_set_password' => false])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
