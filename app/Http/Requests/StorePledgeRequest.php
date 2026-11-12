<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\PledgeStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePledgeRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->filled('phone') ? $this->input('phone') : null,
            'note' => $this->filled('note') ? $this->input('note') : null,
            'guest_id' => $this->filled('guest_id') ? $this->input('guest_id') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'donor_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'status' => ['required', Rule::enum(PledgeStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            'guest_id' => [
                'nullable',
                'integer',
                Rule::exists('guests', 'id')->where('event_id', $event?->id),
            ],
        ];
    }
}
