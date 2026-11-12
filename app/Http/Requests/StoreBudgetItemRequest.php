<?php

namespace App\Http\Requests;

use App\BudgetKind;
use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetItemRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'kind' => ['required', Rule::enum(BudgetKind::class)],
            'label' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'committee_id' => [
                'nullable',
                'integer',
                Rule::exists('committees', 'id')->where('event_id', $event instanceof Event ? $event->id : 0),
            ],
            'sector' => ['nullable', 'required_with:committee_id', 'string', 'max:120'],
        ];
    }
}
