<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchasePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless($this->user()?->can('update', $this->route('event')) ?? false, 404);

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('is_active', true)],
        ];
    }
}
