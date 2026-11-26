<?php

namespace App\Http\Controllers;

use App\Actions\CancelDemoTrial;
use App\Actions\GrantDemoTrial;
use App\Actions\SendTrialWhatsApp;
use App\Http\Requests\StoreDemoRequest;
use App\Models\DemoRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DemoRequestController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin() ?? false, 404);

        return view('demos.index', [
            'demos' => DemoRequest::query()->latest()->paginate(20),
        ]);
    }

    public function store(StoreDemoRequest $request): RedirectResponse
    {
        DemoRequest::query()->create($request->validated());

        return redirect()
            ->route('home')
            ->withFragment('demo')
            ->with('status', __('Demo request received. We will contact you on the phone number you gave us.'));
    }

    public function grant(Request $request, DemoRequest $demoRequest, GrantDemoTrial $grantDemoTrial): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() ?? false, 404);

        $validated = $request->validateWithBag('trial-'.$demoRequest->id, [
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ], [
            'days.required' => __('Choose between 1 and 365 days.'),
            'days.integer' => __('Choose between 1 and 365 days.'),
            'days.min' => __('Choose between 1 and 365 days.'),
            'days.max' => __('Choose between 1 and 365 days.'),
        ]);

        try {
            $result = $grantDemoTrial->handle($demoRequest, $validated['days']);
        } catch (ValidationException $exception) {
            throw $exception->errorBag('trial-'.$demoRequest->id);
        }

        $message = $this->trialStatus($result['days'], $result['smsSent'], $result['mailSent']);
        $flash = $result['smsSent'] && $result['mailSent'] ? 'status' : 'error';

        return redirect()
            ->route('demos.index')
            ->with($flash, $message);
    }

    public function whatsapp(Request $request, DemoRequest $demoRequest, SendTrialWhatsApp $sendTrialWhatsApp): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() ?? false, 404);

        try {
            $url = $sendTrialWhatsApp->handle($demoRequest);
        } catch (ValidationException $exception) {
            throw $exception->errorBag('trial-'.$demoRequest->id);
        }

        return redirect()->away($url);
    }

    public function cancel(Request $request, DemoRequest $demoRequest, CancelDemoTrial $cancelDemoTrial): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() ?? false, 404);

        try {
            $demo = $cancelDemoTrial->handle($demoRequest);
        } catch (ValidationException $exception) {
            throw $exception->errorBag('trial-'.$demoRequest->id);
        }

        $accessUntil = $demo->user?->subscription_ends_at;
        $status = $accessUntil !== null && $accessUntil->isFuture()
            ? __('The trial for :name is cancelled. Paid access continues until :date.', [
                'name' => $demo->name,
                'date' => $accessUntil->timezone(config('app.timezone'))->translatedFormat('j M Y, g:i A'),
            ])
            : __('The trial for :name is cancelled.', ['name' => $demo->name]);

        return redirect()
            ->route('demos.index')
            ->with('status', $status);
    }

    public function destroy(Request $request, DemoRequest $demoRequest): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin() ?? false, 404);

        $name = $demoRequest->name;
        $demoRequest->delete();

        return redirect()
            ->route('demos.index')
            ->with('status', __('The demo request from :name was removed.', ['name' => $name]));
    }

    private function trialStatus(int $days, bool $smsSent, bool $mailSent): string
    {
        if ($smsSent && $mailSent) {
            return __('A :days-day trial is active. The link was sent by SMS and email.', ['days' => $days]);
        }

        if ($mailSent) {
            return __('A :days-day trial is active. The email was sent, but the SMS was not.', ['days' => $days]);
        }

        if ($smsSent) {
            return __('A :days-day trial is active. The SMS was sent, but the email was not.', ['days' => $days]);
        }

        if (config('mail.default') === 'log') {
            return __('A :days-day trial is active, but nothing was delivered. The SMS gateway is not set up, and email is only saved in the application log. Copy the trial link from the list.', ['days' => $days]);
        }

        return __('A :days-day trial is active, but the SMS and email were not sent. Copy the trial link from the list.', ['days' => $days]);
    }
}
