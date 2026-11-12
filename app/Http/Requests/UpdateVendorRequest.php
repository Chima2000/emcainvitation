<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\VendorStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(VendorStatus::class)],
        ];
    }
}
