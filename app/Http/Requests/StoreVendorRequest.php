<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\VendorStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->filled('phone') ? $this->input('phone') : null,
            'price' => $this->filled('price') ? $this->input('price') : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(self::categories())],
            'phone' => ['nullable', 'string', 'max:30'],
            'price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'status' => ['required', Rule::enum(VendorStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return ['Catering', 'Venue', 'Photography', 'Music', 'Decor', 'Transport', 'Clothing', 'Other'];
    }
}
