<?php

namespace App\Http\Requests;

use App\RsvpStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rsvp_status' => ['required', Rule::in([RsvpStatus::Attending->value, RsvpStatus::Declined->value])],
            'party_size' => ['required_if:rsvp_status,attending', 'nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rsvp_status.required' => 'Please tell the host whether you can attend.',
            'rsvp_status.in' => 'Please tell the host whether you can attend.',
            'party_size.required_if' => 'Please say how many people are coming.',
        ];
    }
}
