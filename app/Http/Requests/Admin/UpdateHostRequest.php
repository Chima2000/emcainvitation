<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesPhoneInput;
use App\Models\User;
use App\PhoneInput;
use App\Rules\TanzanianPhoneNumber;
use App\Support\TanzaniaLocations;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateHostRequest extends FormRequest
{
    use NormalizesPhoneInput;

    public function authorize(): bool
    {
        $account = $this->route('user');

        return $account instanceof User && ($this->user()?->can('update', $account) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => [
                'required',
                'string',
                new TanzanianPhoneNumber(PhoneInput::International),
                Rule::unique('users', 'phone')->ignore($this->route('user')),
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            ...TanzaniaLocations::rules($this->all(), required: false),
        ];
    }
}
