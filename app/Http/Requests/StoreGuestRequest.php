<?php

namespace App\Http\Requests;

use App\GuestGender;
use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\RsvpStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->filled('phone') ? $this->input('phone') : null,
            'email' => $this->filled('email') ? $this->input('email') : null,
            'gender' => $this->filled('gender') ? $this->input('gender') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', Rule::enum(GuestGender::class)],
            'party_size' => ['required', 'integer', 'min:1', 'max:30'],
            'rsvp_status' => ['nullable', Rule::enum(RsvpStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
