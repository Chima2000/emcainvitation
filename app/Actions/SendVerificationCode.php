<?php

namespace App\Actions;

use App\Support\OneTimeCode;
use App\Support\SmsGateway;
use App\Support\SystemSettings;

class SendVerificationCode
{
    public const string Registration = 'registration';

    public const string PasswordReset = 'password-reset';

    public function __construct(
        private OneTimeCode $oneTimeCode,
        private SmsGateway $smsGateway,
    ) {}

    /**
     * Issue a fresh code and confirm the gateway accepted it before the visitor proceeds.
     */
    public function handle(string $purpose, string $phone, string $name = ''): bool
    {
        if (! $this->smsGateway->isConfigured()) {
            return false;
        }

        $code = $this->oneTimeCode->issue($purpose, $phone);

        $template = $purpose === self::PasswordReset
            ? SystemSettings::passwordResetCodeMessage()
            : SystemSettings::registrationCodeMessage();

        $sent = $this->smsGateway->send($phone, SystemSettings::fillMessage($template, [
            'code' => $code,
            'minutes' => SystemSettings::codeLifetimeMinutes(),
            'app' => config('app.name'),
            'name' => $name,
        ]));

        if (! $sent) {
            $this->oneTimeCode->forget($purpose, $phone);

            return false;
        }

        return true;
    }
}
