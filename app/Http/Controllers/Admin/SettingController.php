<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTestSmsRequest;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Support\QueueHealth;
use App\Support\SmsGateway;
use App\Support\SystemSettings;
use App\Support\TanzanianPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class SettingController extends Controller
{
    public function edit(QueueHealth $queueHealth): View
    {
        return view('admin.settings.edit', [
            'queue' => $queueHealth->snapshot(),
            'worksFromScheduler' => (bool) config('queue.work_from_scheduler'),
            'sms' => [
                'base_url' => SystemSettings::smsBaseUrl(),
                'username' => SystemSettings::smsUsername(),
                'sender_id' => SystemSettings::smsSenderId(),
                'verify_ssl' => SystemSettings::smsVerifiesSsl(),
                'has_password' => SystemSettings::smsPassword() !== '',
                'configured' => SystemSettings::smsIsConfigured(),
            ],
            'verifyPhone' => SystemSettings::registrationVerifiesPhone(),
            'codeLifetime' => SystemSettings::codeLifetimeMinutes(),
            'registrationMessage' => SystemSettings::registrationCodeMessage(),
            'passwordResetMessage' => SystemSettings::passwordResetCodeMessage(),
            'adminPhone' => auth()->user()->localPhone(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        SystemSettings::update([
            'base_url' => $request->validated('sms_base_url'),
            'username' => $request->validated('sms_username'),
            'password' => $request->validated('sms_password'),
            'sender_id' => $request->validated('sms_sender_id'),
            'verify_ssl' => $request->boolean('sms_verify_ssl'),
            'verify_phone' => $request->boolean('registration_verify_phone'),
            'code_lifetime' => (int) $request->validated('code_lifetime'),
            'registration_message' => $request->validated('registration_message'),
            'password_reset_message' => $request->validated('password_reset_message'),
        ]);

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', __('Settings saved.'));
    }

    public function testSms(SendTestSmsRequest $request, SmsGateway $smsGateway): RedirectResponse
    {
        $phone = $request->phone();

        $sent = $smsGateway->send($phone, __('Test message from :app. SMS delivery is working.', ['app' => config('app.name')]));

        if (! $sent) {
            return redirect()
                ->route('admin.settings.edit')
                ->withErrors(['test_phone' => __('The SMS was not sent. Check the gateway details and the logs.')], 'testSms')
                ->withInput();
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', __('Test SMS sent to :phone.', ['phone' => TanzanianPhone::toLocal($phone)]));
    }

    public function flushFailedJobs(FailedJobProviderInterface $failedJobs): RedirectResponse
    {
        $failedJobs->flush();

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', __('The failed jobs list was cleared.'));
    }
}
