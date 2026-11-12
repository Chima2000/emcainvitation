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

class StoreHostRequest extends FormRequest
{
    use NormalizesPhoneInput;

    protected $redirectRoute = 'admin.users.index';

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => ['required', 'string', new TanzanianPhoneNumber(PhoneInput::International), Rule::unique('users', 'phone')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            ...TanzaniaLocations::rules($this->all(), required: false),
        ];
    }
}
