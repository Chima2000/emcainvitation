<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreEventTaskRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'due_on' => $this->filled('due_on') ? $this->input('due_on') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'due_on' => ['nullable', 'date', 'after_or_equal:today'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $redirect = $this->getRedirectUrl();

        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo(str_contains($redirect, '#') ? $redirect : $redirect.'#tasks');
    }
}
