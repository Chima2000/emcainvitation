<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCommitteeGuestsRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected function prepareForValidation(): void
    {
        if ($this->exists('guest_ids')) {
            $this->merge([
                'participant_ids' => $this->input('guest_ids'),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $event = $this->route('event');
        $eventId = $event instanceof Event ? $event->id : 0;

        return [
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('guests', 'id')->where('event_id', $eventId),
            ],
        ];
    }
}
