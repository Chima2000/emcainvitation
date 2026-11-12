<?php

namespace App\Http\Requests\Admin;

use App\MessageChannel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackageRequest extends FormRequest
{
    protected $errorBag = 'package';

    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $package = $this->route('package');

        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('packages', 'name')->ignore($package)],
            'summary' => ['nullable', 'string', 'max:255'],
            'guest_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'channel' => ['required', Rule::enum(MessageChannel::class)],
            'reminder_rounds' => ['required', 'integer', 'min:0', 'max:10'],
            'includes_thanks' => ['sometimes', 'boolean'],
            'includes_pledges' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function packageAttributes(): array
    {
        return [
            ...$this->safe()->except(['includes_thanks', 'includes_pledges', 'is_featured', 'is_active', 'sort_order']),
            'includes_thanks' => $this->boolean('includes_thanks'),
            'includes_pledges' => $this->boolean('includes_pledges'),
            'is_featured' => $this->boolean('is_featured'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->integer('sort_order'),
        ];
    }
}
