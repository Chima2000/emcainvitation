<?php

namespace App\Http\Requests;

use App\Models\EventTask;
use App\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateEventTaskRequest extends StoreEventTaskRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isStatusToggle()) {
            return;
        }

        parent::prepareForValidation();

        $task = $this->route('task');

        if ($task instanceof EventTask) {
            $this->errorBag = 'task-'.$task->getKey();
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isStatusToggle()) {
            return [
                'status' => ['required', Rule::enum(TaskStatus::class)],
            ];
        }

        $task = $this->route('task');
        $currentDue = $task instanceof EventTask ? $task->due_on?->toDateString() : null;

        return [
            'title' => ['required', 'string', 'max:160'],
            'due_on' => [
                'nullable',
                'date',
                Rule::when($this->input('due_on') !== $currentDue, ['after_or_equal:today']),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $task = $this->route('task');

        if ($task instanceof EventTask && ! $this->isStatusToggle()) {
            session()->flash('task_edit', $task->getKey());
        }

        parent::failedValidation($validator);
    }

    private function isStatusToggle(): bool
    {
        return ! $this->exists('title') && $this->exists('status');
    }
}
