<?php

namespace App\Http\Requests;

use App\CardFont;
use App\CardLayout;
use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventDesignRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'invitation_template_id' => $this->filled('invitation_template_id') ? $this->input('invitation_template_id') : null,
            'headline' => $this->filled('headline') ? $this->input('headline') : null,
            'message' => $this->filled('message') ? $this->input('message') : null,
            'dress_code' => $this->filled('dress_code') ? $this->input('dress_code') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $color = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'invitation_template_id' => ['nullable', 'integer', Rule::exists('invitation_templates', 'id')],
            'paper' => $color,
            'ink' => $color,
            'accent' => $color,
            'font' => ['required', Rule::enum(CardFont::class)],
            'layout' => ['required', Rule::enum(CardLayout::class)],
            'headline' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:2000'],
            'dress_code' => ['nullable', 'string', 'max:120'],
            'background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_background' => ['sometimes', 'boolean'],
            'hide_template_background' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'paper' => __('Paper colour'),
            'ink' => __('Text colour'),
            'accent' => __('Accent colour'),
            'background' => __('Background picture'),
        ];
    }
}
