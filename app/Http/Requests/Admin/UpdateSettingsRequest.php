<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $message = ['required', 'string', 'max:320', 'regex:/\{code\}/'];

        return [
            'sms_base_url' => ['required', 'url:http,https', 'max:255'],
            'sms_username' => ['required', 'string', 'max:120'],
            'sms_password' => ['nullable', 'string', 'max:120'],
            'sms_sender_id' => ['required', 'string', 'max:11', 'regex:/^[A-Za-z0-9 .\-]+$/'],
            'sms_verify_ssl' => ['sometimes', 'boolean'],
            'registration_verify_phone' => ['sometimes', 'boolean'],
            'code_lifetime' => ['required', 'integer', 'min:2', 'max:60'],
            'registration_message' => $message,
            'password_reset_message' => $message,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'registration_message.regex' => __('The message must contain {code} so the user receives the code.'),
            'password_reset_message.regex' => __('The message must contain {code} so the user receives the code.'),
            'sms_sender_id.regex' => __('Use only letters, numbers, spaces, dots and dashes in the sender ID.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sms_base_url' => __('Gateway URL'),
            'sms_username' => __('Username'),
            'sms_password' => __('Password'),
            'sms_sender_id' => __('Sender ID'),
            'code_lifetime' => __('Code lifetime'),
            'registration_message' => __('Registration code message'),
            'password_reset_message' => __('Password reset message'),
        ];
    }
}
