<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Support\TanzaniaLocations;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('profile.edit', [
            'user' => $user,
            'profileRequired' => ! $user->hasCompletedProfile(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $user->update([
            ...TanzaniaLocations::normalize($request->validated()),
            'profile_completed_at' => now(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', __('Your profile details have been saved.'));
    }
}
