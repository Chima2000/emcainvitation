<?php

namespace App\Http\Requests\Auth;

use App\PhoneInput;
use App\Rules\TanzanianPhoneNumber;
use App\Support\TanzanianPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'phone' => ['required', 'string', new TanzanianPhoneNumber(PhoneInput::Registration), Rule::unique('users', 'phone')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
