<?php

namespace App\Support;

use App\PhoneInput;

final class TanzanianPhone
{
    public static function countryCode(): string
    {
        return (string) config('emca.phone.country_code');
    }

    public static function normalize(string $value, PhoneInput $input): ?string
    {
        $compact = preg_replace('/\s+/', '', trim($value)) ?? '';
        $countryCode = self::countryCode();

        if ($input === PhoneInput::Registration) {
            if (preg_match('/^0[67]\d{8}$/', $compact) === 1) {
                return $countryCode.substr($compact, 1);
            }

            if (preg_match('/^'.preg_quote($countryCode, '/').'[67]\d{8}$/', $compact) === 1) {
                return $compact;
            }

            $digitsCountryCode = ltrim($countryCode, '+');

            if (preg_match('/^'.preg_quote($digitsCountryCode, '/').'[67]\d{8}$/', $compact) === 1) {
                return $countryCode.substr($compact, strlen(ltrim($countryCode, '+')));
            }

            return null;
        }

        if ($input === PhoneInput::International) {
            return preg_match('/^'.preg_quote($countryCode, '/').'[67]\d{8}$/', $compact) === 1
                ? $compact
                : null;
        }

        if (preg_match('/^0[67]\d{8}$/', $compact) !== 1) {
            return null;
        }

        return $countryCode.substr($compact, 1);
    }

    public static function toLocal(string $international): string
    {
        $countryCode = self::countryCode();

        if (! str_starts_with($international, $countryCode)) {
            return $international;
        }

        return '0'.substr($international, strlen($countryCode));
    }
}
