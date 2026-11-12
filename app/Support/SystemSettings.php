<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Admin-editable settings. A saved value wins; otherwise the value from config (.env) is used.
 */
final class SystemSettings
{
    public static function smsBaseUrl(): string
    {
        return self::text('sms.base_url', (string) config('services.sms.base_url'));
    }

    public static function smsUsername(): string
    {
        return self::text('sms.username', (string) config('services.sms.username'));
    }

    public static function smsPassword(): string
    {
        $saved = Setting::read('sms.password');

        if (blank($saved)) {
            return (string) config('services.sms.password');
        }

        try {
            return Crypt::decryptString($saved);
        } catch (DecryptException) {
            return '';
        }
    }

    public static function hasSavedSmsPassword(): bool
    {
        return filled(Setting::read('sms.password'));
    }

    public static function smsSenderId(): string
    {
        return self::text('sms.sender_id', (string) config('services.sms.from'));
    }

    public static function smsVerifiesSsl(): bool
    {
        return self::flag('sms.verify_ssl', (bool) config('services.sms.verify_ssl', true));
    }

    public static function smsIsConfigured(): bool
    {
        return self::smsBaseUrl() !== '' && self::smsUsername() !== '' && self::smsPassword() !== '';
    }

    public static function registrationVerifiesPhone(): bool
    {
        return self::flag('registration.verify_phone', true);
    }

    /**
     * Phone verification only runs when the admin switched it on and SMS can actually be delivered.
     */
    public static function registrationRequiresPhoneCode(): bool
    {
        return self::registrationVerifiesPhone() && self::smsIsConfigured();
    }

    public static function subscriptionRequired(): bool
    {
        return self::flag('subscription.required', true);
    }

    public static function codeLifetimeMinutes(): int
    {
        $minutes = (int) self::text('otp.lifetime', '10');

        return $minutes > 0 ? $minutes : 10;
    }

    public static function registrationCodeMessage(): string
    {
        return self::text('messages.registration_code', self::defaultRegistrationCodeMessage());
    }

    public static function passwordResetCodeMessage(): string
    {
        return self::text('messages.password_reset_code', self::defaultPasswordResetCodeMessage());
    }

    public static function defaultRegistrationCodeMessage(): string
    {
        return __('Your {app} verification code is {code}. It expires in {minutes} minutes.');
    }

    public static function defaultPasswordResetCodeMessage(): string
    {
        return __('Hello {name}, your {app} password reset code is {code}. It expires in {minutes} minutes. Ignore this if you did not ask for it.');
    }

    /**
     * @param  array<string, string|int>  $values
     */
    public static function fillMessage(string $template, array $values): string
    {
        $replacements = [];

        foreach ($values as $key => $value) {
            $replacements['{'.$key.'}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }

    /**
     * @param  array{base_url: string, username: string, password: ?string, sender_id: string, verify_ssl: bool, verify_phone: bool, code_lifetime: int, registration_message: string, password_reset_message: string}  $values
     */
    public static function update(array $values): void
    {
        $settings = [
            'sms.base_url' => trim($values['base_url']),
            'sms.username' => trim($values['username']),
            'sms.sender_id' => trim($values['sender_id']),
            'sms.verify_ssl' => $values['verify_ssl'] ? '1' : '0',
            'registration.verify_phone' => $values['verify_phone'] ? '1' : '0',
            'otp.lifetime' => (string) $values['code_lifetime'],
            'messages.registration_code' => trim($values['registration_message']),
            'messages.password_reset_code' => trim($values['password_reset_message']),
        ];

        if (filled($values['password'])) {
            $settings['sms.password'] = Crypt::encryptString($values['password']);
        }

        Setting::store($settings);
    }

    public static function setSubscriptionRequired(bool $required): void
    {
        Setting::store(['subscription.required' => $required ? '1' : '0']);
    }

    private static function text(string $key, string $default): string
    {
        $saved = Setting::read($key);

        return filled($saved) ? (string) $saved : $default;
    }

    private static function flag(string $key, bool $default): bool
    {
        return Setting::isSaved($key) ? Setting::read($key) === '1' : $default;
    }
}
