<?php

namespace App\Rules;

use App\PhoneInput;
use App\Support\TanzanianPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class TanzanianPhoneNumber implements ValidationRule
{
    public function __construct(private PhoneInput $input) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || TanzanianPhone::normalize($value, $this->input) === null) {
            $fail($this->message());
        }
    }

    public function message(): string
    {
        if ($this->input === PhoneInput::Registration) {
            return __('Enter a Tanzanian phone number as 10 digits starting with 0 or 12 digits starting with 255, for example 0692586046 or 255692586046.');
        }

        return $this->input === PhoneInput::Local
            ? __('Enter the phone number starting with 0, for example 0692586046.')
            : __('Enter the phone number starting with +255, for example +255692586046.');
    }
}
