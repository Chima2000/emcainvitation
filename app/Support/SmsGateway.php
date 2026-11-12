<?php

namespace App\Support;

use App\Exceptions\SmsNotSent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends single text messages through the messaging-service.co.tz HTTP gateway.
 */
class SmsGateway
{
    /**
     * Seconds to wait for the gateway to answer. Kept short so a slow gateway frees the worker for the next message.
     */
    public const int TimeoutSeconds = 10;

    public const int ConnectTimeoutSeconds = 4;

    public function isConfigured(): bool
    {
        return SystemSettings::smsIsConfigured();
    }

    /**
     * Send straight away and report success, for screens that need an immediate answer such as the admin test SMS.
     */
    public function send(string $phone, string $message): bool
    {
        try {
            $this->deliver($phone, $message);
        } catch (SmsNotSent $exception) {
            Log::warning('SMS not sent.', ['phone' => $this->recipient($phone), 'reason' => $exception->getMessage()]);

            return false;
        }

        return true;
    }

    /**
     * Send one message. Problems that may pass (timeouts, gateway errors, rate limits) are marked worth retrying;
     * problems that will not (missing settings, a rejected number or login) are not.
     *
     * @throws SmsNotSent
     */
    public function deliver(string $phone, string $message): void
    {
        $recipient = $this->recipient($phone);

        if (! $this->isConfigured()) {
            throw SmsNotSent::permanently('The SMS gateway is not set up.');
        }

        if ($recipient === '' || trim($message) === '') {
            throw SmsNotSent::permanently('The phone number or the message is empty.');
        }

        try {
            $response = Http::timeout(self::TimeoutSeconds)
                ->connectTimeout(self::ConnectTimeoutSeconds)
                ->withOptions([
                    'allow_redirects' => true,
                    'verify' => SystemSettings::smsVerifiesSsl(),
                ])
                ->get(rtrim(SystemSettings::smsBaseUrl(), '?&'), [
                    'username' => SystemSettings::smsUsername(),
                    'password' => SystemSettings::smsPassword(),
                    'from' => SystemSettings::smsSenderId(),
                    'to' => $recipient,
                    'text' => $message,
                ]);
        } catch (ConnectionException $exception) {
            throw SmsNotSent::temporarily('The SMS gateway could not be reached: '.$exception->getMessage());
        }

        if ($response->successful()) {
            return;
        }

        $reason = 'The SMS gateway answered with status '.$response->status().': '.mb_strimwidth(trim($response->body()), 0, 150, '...');

        if ($response->serverError() || $response->status() === 429 || $response->status() === 408) {
            throw SmsNotSent::temporarily($reason);
        }

        throw SmsNotSent::permanently($reason);
    }

    private function recipient(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
