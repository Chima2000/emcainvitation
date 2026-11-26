<?php

namespace App\Http\Requests;

use App\EventStatus;
use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends StoreEventRequest
{
    protected $errorBag = 'default';

    protected function getRedirectUrl(): string
    {
        return url()->previous();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string|Closure>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['event_date'] = ['required', 'date', $this->notBeforeTodayUnlessUnchanged()];
        $rules['status'] = ['required', Rule::enum(EventStatus::class)];

        return $rules;
    }

    /**
     * A new date cannot be before today. An event that is already in the past may keep its saved date.
     */
    private function notBeforeTodayUnlessUnchanged(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $chosen = Carbon::parse((string) $value)->timezone(config('app.timezone'));
            $today = now()->timezone(config('app.timezone'))->startOfDay();

            if ($chosen->greaterThanOrEqualTo($today)) {
                return;
            }

            $event = $this->route('event');
            $stored = $event instanceof Event
                ? $event->event_date->timezone(config('app.timezone'))->format('Y-m-d H:i')
                : null;

            if ($stored !== null && $chosen->format('Y-m-d H:i') === $stored) {
                return;
            }

            $fail(__('Choose today or a later date.'));
        };
    }
}
