<?php

namespace App\Http\Requests;

use App\EventStatus;
use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreEventRequest extends FormRequest
{
    use AuthorizesOwnedEvent;

    protected $errorBag = 'createEvent';

    protected function getRedirectUrl(): string
    {
        return route('events.index', ['new' => 1]);
    }

    protected function prepareForValidation(): void
    {
        $usesOrganizationName = $this->boolean('host_name_is_organization');
        $organizationName = $this->input('organization_name');

        $this->merge([
            'host_name' => $usesOrganizationName && is_string($organizationName)
                ? trim($organizationName)
                : (string) $this->user()?->name,
            'host_name_is_organization' => $usesOrganizationName,
            'partner_name' => $this->filled('partner_name') ? $this->input('partner_name') : null,
            'headline' => $this->filled('headline') ? $this->input('headline') : null,
            'dress_code' => $this->filled('dress_code') ? $this->input('dress_code') : null,
            'dress_code_colors' => is_array($this->input('dress_code_colors'))
                ? array_values(array_filter($this->input('dress_code_colors'), fn (mixed $color): bool => $color !== null && $color !== ''))
                : $this->input('dress_code_colors'),
            'message' => $this->filled('message') ? $this->input('message') : null,
            'invitation_template_id' => $this->filled('invitation_template_id') ? $this->input('invitation_template_id') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', $this->categoryRule()],
            'status' => ['nullable', Rule::enum(EventStatus::class)],
            'host_name' => ['required', 'string', 'max:120'],
            'host_name_is_organization' => ['required', 'boolean'],
            'organization_name' => ['nullable', 'required_if_accepted:host_name_is_organization', 'string', 'max:120'],
            'partner_name' => ['nullable', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:2000'],
            'venue' => ['required', 'string', 'max:200'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'dress_code' => ['nullable', 'string', 'max:120'],
            'dress_code_colors' => ['nullable', 'array'],
            'dress_code_colors.*' => ['required', 'regex:/\\A#[0-9A-Fa-f]{6}\\z/'],
            'invitation_template_id' => ['nullable', 'integer', Rule::exists('invitation_templates', 'id')],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Switched-on categories only, though an event may keep a category the admin later switched off.
     */
    protected function categoryRule(): Exists
    {
        $event = $this->route('event');
        $currentType = $event instanceof Event ? $event->getRawOriginal('type') : null;

        return Rule::exists('event_categories', 'slug')->where(
            fn (Builder $query) => $query->where('is_active', true)->when(
                $currentType,
                fn (Builder $query) => $query->orWhere('slug', $currentType),
            ),
        );
    }
}
