<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportGuestsRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}
