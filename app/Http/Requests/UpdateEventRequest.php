<?php

namespace App\Http\Requests;

use App\EventStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends StoreEventRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['event_date'] = ['required', 'date'];
        $rules['status'] = ['required', Rule::enum(EventStatus::class)];

        return $rules;
    }
}
