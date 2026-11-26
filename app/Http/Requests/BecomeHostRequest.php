<?php

namespace App\Http\Requests;

use App\PhoneInput;
use App\Rules\TanzanianPhoneNumber;
use App\Support\TanzanianPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class BecomeHostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isLead() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/\s+/', '', (string) $this->input('phone')) ?? '';

        $this->merge([
            'phone' => TanzanianPhone::normalize($phone, PhoneInput::Registration) ?? $phone,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => ['required', 'string', new TanzanianPhoneNumber(PhoneInput::Registration), Rule::unique('users', 'phone')->ignore($this->user()?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()?->id)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
