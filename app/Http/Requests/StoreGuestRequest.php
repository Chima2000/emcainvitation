<?php

namespace App\Http\Requests;

use App\GuestGender;
use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\Models\Event;
use App\Models\Guest;
use App\RsvpStatus;
use App\Support\GuestRoster;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGuestRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected function prepareForValidation(): void
    {
        if (! $this->filled('name') && $this->filled('contact_id')) {
            $contact = $this->user()?->contacts()->find($this->integer('contact_id'));

            if ($contact !== null) {
                $this->merge([
                    'name' => $contact->name,
                    'phone' => $this->filled('phone') ? $this->input('phone') : $contact->phone,
                    'email' => $this->filled('email') ? $this->input('email') : $contact->email,
                    'gender' => $this->filled('gender') ? $this->input('gender') : $contact->gender?->value,
                ]);
            }
        }

        $this->merge([
            'phone' => $this->filled('phone') ? $this->input('phone') : null,
            'email' => $this->filled('email') ? $this->input('email') : null,
            'gender' => $this->filled('gender') ? $this->input('gender') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
            'table_name' => $this->filled('table_name') ? $this->input('table_name') : null,
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
            'table_name' => ['nullable', 'string', 'max:40'],
            'rsvp_status' => ['nullable', Rule::enum(RsvpStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['name', 'phone', 'email'])) {
                    return;
                }

                $event = $this->route('event');

                if (! $event instanceof Event) {
                    return;
                }

                if ($event->trialGuestsRemaining() === 0) {
                    $validator->errors()->add('name', __('A trial can list :count guests. Become a host to add more.', [
                        'count' => Event::TrialGuestLimit,
                    ]));

                    return;
                }

                $guest = $this->route('guest');
                $roster = GuestRoster::forEvent($event, $guest instanceof Guest ? $guest->id : null);
                $conflicts = $roster->conflicts($this->string('name')->toString(), $this->input('phone'), $this->input('email'));

                foreach ($conflicts as $field) {
                    $validator->errors()->add($field, $field === 'phone'
                        ? __(':name is already on this event with this phone.', [
                            'name' => $roster->phoneHolder($this->input('phone')) ?? __('This guest'),
                        ])
                        : __('This guest is already on the event.'));
                }
            },
        ];
    }
}
