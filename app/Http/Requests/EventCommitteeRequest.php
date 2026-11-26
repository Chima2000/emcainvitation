<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\Models\Committee;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EventCommitteeRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected function prepareForValidation(): void
    {
        if (! $this->filled('door_guest_ids')) {
            $this->merge(['door_guest_ids' => []]);
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
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('committees', 'name')
                    ->where('event_id', $eventId)
                    ->ignore($this->route('committee') instanceof Committee ? $this->route('committee')->id : null),
            ],
            'leader_guest_id' => [
                'required',
                'integer',
                Rule::exists('guests', 'id')->where('event_id', $eventId),
            ],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('guests', 'id')->where('event_id', $eventId),
            ],
            'is_door' => ['boolean'],
            'door_guest_ids' => ['array'],
            'door_guest_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('guests', 'id')->where('event_id', $eventId),
            ],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasParticipantErrors = collect($validator->errors()->keys())
                    ->contains(fn (string $key): bool => str_starts_with($key, 'participant_ids.'));

                if ($validator->errors()->hasAny(['leader_guest_id', 'participant_ids']) || $hasParticipantErrors) {
                    return;
                }

                $participantIds = array_map('intval', $this->input('participant_ids', []));

                if (! in_array($this->integer('leader_guest_id'), $participantIds, true)) {
                    $validator->errors()->add('leader_guest_id', __('The committee leader must also be selected as a participant.'));
                }

                if (! $this->boolean('is_door')) {
                    return;
                }

                $doorGuestIds = array_map('intval', $this->input('door_guest_ids', []));

                if ($doorGuestIds === [] || array_diff($doorGuestIds, $participantIds) !== []) {
                    $validator->errors()->add('door_guest_ids', __('Each door link must belong to a selected member of this committee.'));
                }

                $event = $this->route('event');
                $committee = $this->route('committee');
                $alreadyHasDoor = Committee::query()
                    ->where('event_id', $event instanceof Event ? $event->id : 0)
                    ->where('is_door', true)
                    ->when($committee instanceof Committee, fn ($query) => $query->whereKeyNot($committee->id))
                    ->exists();

                if ($alreadyHasDoor) {
                    $validator->errors()->add('is_door', __('This event already has a door committee.'));
                }
            },
        ];
    }
}
