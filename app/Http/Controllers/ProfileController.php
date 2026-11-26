<?php

namespace App\Http\Controllers;

use App\Actions\ContinueHostRegistration;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Support\ChosenInvitationTemplate;
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
            'profileRequired' => ! $user->isLead() && ! $user->hasCompletedProfile(),
        ]);
    }

    public function update(UpdateProfileRequest $request, ContinueHostRegistration $continueHostRegistration, ChosenInvitationTemplate $chosenInvitationTemplate): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $user->update([
            ...TanzaniaLocations::normalize($request->validated()),
            'profile_completed_at' => now(),
        ]);

        if ($request->session()->get(ContinueHostRegistration::PendingSignIn)) {
            return $continueHostRegistration->afterProfile($request);
        }

        $next = $chosenInvitationTemplate->eventUrl($request);

        if ($next === null && $request->session()->pull(SessionController::OpenTemplatesAfterAuth)) {
            $next = route('template-library.index');
        }

        return redirect()
            ->to($next ?? route('dashboard'))
            ->with('status', __('Your profile details have been saved.'));
    }
}
