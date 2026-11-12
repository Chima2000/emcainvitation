<?php

namespace App\Http\Requests\Admin;

use App\PhoneInput;
use App\Rules\TanzanianPhoneNumber;
use App\Support\TanzanianPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendTestSmsRequest extends FormRequest
{
    protected $errorBag = 'testSms';

    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'test_phone' => preg_replace('/\s+/', '', (string) $this->input('test_phone')) ?? '',
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'test_phone' => ['required', 'string', new TanzanianPhoneNumber(PhoneInput::Local)],
        ];
    }

    public function phone(): string
    {
        return (string) TanzanianPhone::normalize($this->validated('test_phone'), PhoneInput::Local);
    }
}
