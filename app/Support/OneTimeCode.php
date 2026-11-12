<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Six-digit codes sent by SMS. Only a hash is kept, and a code dies after a few wrong tries.
 */
class OneTimeCode
{
    public const int MaxAttempts = 5;

    public function issue(string $purpose, string $subject): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(SystemSettings::codeLifetimeMinutes());

        Cache::put($this->key($purpose, $subject), [
            'hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => $expiresAt->getTimestamp(),
        ], $expiresAt);

        return $code;
    }

    public function verify(string $purpose, string $subject, string $code): bool
    {
        $key = $this->key($purpose, $subject);
        $entry = Cache::get($key);

        if (! is_array($entry) || $entry['attempts'] >= self::MaxAttempts || $entry['expires_at'] <= now()->getTimestamp()) {
            return false;
        }

        if (Hash::check(trim($code), $entry['hash'])) {
            Cache::forget($key);

            return true;
        }

        $entry['attempts']++;
        Cache::put($key, $entry, now()->setTimestamp($entry['expires_at']));

        return false;
    }

    public function forget(string $purpose, string $subject): void
    {
        Cache::forget($this->key($purpose, $subject));
    }

    private function key(string $purpose, string $subject): string
    {
        return 'one-time-code:'.$purpose.':'.sha1($subject);
    }
}
