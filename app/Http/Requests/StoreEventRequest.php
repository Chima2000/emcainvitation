<?php

namespace App\Http\Requests;

use App\EventStatus;
use App\Http\Requests\Concerns\AuthorizesOwnedEvent;
use App\Models\Event;
use App\Models\InvitationTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

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
            'bride_name' => $this->filled('bride_name') ? $this->input('bride_name') : null,
            'groom_name' => $this->filled('groom_name') ? $this->input('groom_name') : null,
            'bride_family' => $this->filled('bride_family') ? $this->input('bride_family') : null,
            'groom_family' => $this->filled('groom_family') ? $this->input('groom_family') : null,
            'ceremony_place' => $this->filled('ceremony_place') ? $this->input('ceremony_place') : null,
            'reception_venue' => $this->filled('reception_venue') ? $this->input('reception_venue') : null,
            'reception_time' => $this->filled('reception_time') ? $this->input('reception_time') : null,
            'arrive_by' => $this->filled('arrive_by') ? $this->input('arrive_by') : null,
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
            'bride_name' => ['nullable', 'string', 'max:120'],
            'groom_name' => ['nullable', 'string', 'max:120'],
            'bride_family' => ['nullable', 'string', 'max:120'],
            'groom_family' => ['nullable', 'string', 'max:120'],
            'ceremony_place' => ['nullable', 'string', 'max:200'],
            'reception_venue' => ['nullable', 'string', 'max:200'],
            'reception_time' => ['nullable', 'string', 'max:40'],
            'arrive_by' => ['nullable', 'string', 'max:80'],
            'dress_code_colors' => ['nullable', 'array'],
            'dress_code_colors.*' => ['required', 'regex:/\\A#[0-9A-Fa-f]{6}\\z/'],
            'invitation_template_id' => ['nullable', 'integer', Rule::exists('invitation_templates', 'id')],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->user()?->isLead() || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $templateId = $this->input('invitation_template_id');

                if ($templateId && InvitationTemplate::query()->whereKey($templateId)->where('host_only', true)->exists()) {
                    $validator->errors()->add(
                        'invitation_template_id',
                        __('This template is for hosts. Choose another, or become a host.'),
                    );
                }
            },
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
