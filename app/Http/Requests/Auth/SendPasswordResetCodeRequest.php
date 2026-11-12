<?php

namespace App\Http\Requests\Auth;

use App\PhoneInput;
use App\Rules\TanzanianPhoneNumber;
use App\Support\TanzanianPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendPasswordResetCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reset_phone' => preg_replace('/\s+/', '', (string) $this->input('reset_phone')) ?? '',
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reset_phone' => ['required', 'string', new TanzanianPhoneNumber(PhoneInput::Local)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reset_phone' => __('phone number')];
    }

    public function phone(): string
    {
        return (string) TanzanianPhone::normalize($this->validated('reset_phone'), PhoneInput::Local);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()->route('login')->withInput()->withErrors($validator),
        );
    }
}
