<?php

namespace App\Http\Requests\Admin;

use App\CardFont;
use App\CardLayout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateRequest extends FormRequest
{
    protected $errorBag = 'template';

    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $color = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'name' => ['required', 'string', 'max:80'],
            'category' => ['required', 'string', Rule::exists('event_categories', 'slug')],
            'price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'paper' => $color,
            'ink' => $color,
            'accent' => $color,
            'font' => ['sometimes', Rule::enum(CardFont::class)],
            'layout' => ['sometimes', Rule::enum(CardLayout::class)],
            'default_headline' => ['nullable', 'string', 'max:160'],
            'default_message' => ['nullable', 'string', 'max:2000'],
            'background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_background' => ['sometimes', 'boolean'],
            'motif' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function templateAttributes(): array
    {
        return $this->safe()->except(['background', 'remove_background']);
    }
}
