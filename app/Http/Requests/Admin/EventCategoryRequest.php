<?php

namespace App\Http\Requests\Admin;

use App\Models\EventCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventCategoryRequest extends FormRequest
{
    protected $errorBag = 'category';

    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name_sw' => $this->filled('name_sw') ? $this->input('name_sw') : null,
            'icon' => $this->filled('icon') ? $this->input('icon') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('event_category');

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('event_categories', 'name')->ignore($category)],
            'name_sw' => ['nullable', 'string', 'max:60'],
            'icon' => ['nullable', Rule::in(EventCategory::iconChoices())],
            'photo' => ['required', Rule::in(array_keys(EventCategory::stockPhotos()))],
            'photo_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photo' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{name: string, name_sw: ?string, icon: ?string, photo: string, sort_order: int, is_active: bool}
     */
    public function categoryAttributes(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'name_sw' => $this->filled('name_sw') ? $this->string('name_sw')->trim()->toString() : null,
            'icon' => $this->validated('icon'),
            'photo' => $this->validated('photo'),
            'sort_order' => $this->integer('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
