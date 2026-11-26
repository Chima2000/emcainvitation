<?php

namespace App\Http\Requests;

use App\PhoneInput;
use App\Rules\TanzanianPhoneNumber;
use App\Support\TanzanianPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemoRequest extends FormRequest
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
            'organization' => $this->filled('organization') ? $this->input('organization') : null,
            'event_type' => $this->filled('event_type') ? $this->input('event_type') : null,
            'guest_count' => $this->filled('guest_count') ? $this->input('guest_count') : null,
            'message' => $this->filled('message') ? $this->input('message') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', new TanzanianPhoneNumber(PhoneInput::Registration)],
            'organization' => ['nullable', 'string', 'max:120'],
            'event_type' => ['nullable', 'string', Rule::exists('event_categories', 'slug')->where('is_active', true)],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
