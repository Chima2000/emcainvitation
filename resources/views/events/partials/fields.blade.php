@php
    $control = 'form-select';
    $current = $event ?? null;
    $fieldId = $fieldId ?? '';
    $errorBag = $errorBag ?? 'default';
    $inputId = fn (string $name): string => $fieldId.$name;
    $invalid = fn (string $name): string => $errors->getBag($errorBag)->has($name) ? ' is-invalid' : '';
    $restoreOldInput = $errors->getBag($errorBag)->any();
    $previous = fn (string $name, mixed $fallback = '') => $restoreOldInput ? old($name, $fallback) : $fallback;
    $hostNameIsOrganization = (bool) $previous('host_name_is_organization', $current?->host_name_is_organization ?? false);
    $dressCodeColors = $previous('dress_code_colors', $current?->dress_code_colors ?? []);
    $dressCodeColors = is_array($dressCodeColors) ? $dressCodeColors : [];
    $selectedTemplateId = (string) $previous('invitation_template_id', $current?->invitation_template_id ?? ($selectedTemplate ?? ''));
    $selectedTemplateName = $templates->first(fn ($template): bool => (string) $template->id === $selectedTemplateId)?->name ?? '';
@endphp

<x-field name="title" :for="$inputId('title')" :bag="$errorBag" label="{{ __('Event title') }}">
    <x-input name="title" :id="$inputId('title')" :bag="$errorBag" value="{{ $previous('title', $current->title ?? '') }}" placeholder="{{ __('For example: Asha and Juma’s wedding') }}" required data-preview-input="title" autocomplete="off" />
</x-field>
<x-field name="type" :for="$inputId('type')" :bag="$errorBag" label="{{ __('Type') }}">
    <select id="{{ $inputId('type') }}" name="type" class="{{ $control }}{{ $invalid('type') }}" required data-preview-input="type">
        @php($typeOptions = $current?->type && ! $types->contains('slug', $current->type->slug) ? $types->concat([$current->type]) : $types)
        @foreach (\App\Models\EventCategory::grouped($typeOptions) as $groupLabel => $group)
            <optgroup label="{{ $groupLabel }}">
                @foreach ($group as $type)
                    <option value="{{ $type->slug }}" @selected($previous('type', $current?->type?->slug ?? ($types->first()?->slug ?? '')) === $type->slug)>{{ $type->label() }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</x-field>
<div class="row">
    <div class="col-md-6">
        <x-field name="host_name" :for="$inputId('host_name')" :bag="$errorBag" label="{{ __('Host') }}">
            <x-input name="host_name" :id="$inputId('host_name')" :bag="$errorBag" value="{{ auth()->user()->name }}" readonly required data-preview-input="host_name" />
        </x-field>
    </div>
    <div class="col-md-6">
        <x-field name="partner_name" :for="$inputId('partner_name')" :bag="$errorBag" label="{{ __('Partner, if any') }}">
            <x-input name="partner_name" :id="$inputId('partner_name')" :bag="$errorBag" value="{{ $previous('partner_name', $current->partner_name ?? '') }}" placeholder="{{ __('For example: Juma') }}" data-preview-input="partner_name" autocomplete="off" />
        </x-field>
    </div>
</div>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="{{ $inputId('host_name_is_organization') }}" name="host_name_is_organization" value="1" @checked($hostNameIsOrganization) data-organization-host-toggle>
    <label class="form-check-label" for="{{ $inputId('host_name_is_organization') }}">{{ __('Use a company or organisation name on the invitation') }}</label>
</div>
<div class="mb-3" data-organization-host-panel @if (! $hostNameIsOrganization) hidden @endif>
    <x-field name="organization_name" :for="$inputId('organization_name')" :bag="$errorBag" label="{{ __('Company or organisation name') }}">
        <x-input name="organization_name" :id="$inputId('organization_name')" :bag="$errorBag" value="{{ $previous('organization_name', $hostNameIsOrganization ? ($current->host_name ?? '') : '') }}" placeholder="{{ __('For example: Upendo Foundation') }}" :disabled="! $hostNameIsOrganization" data-preview-input="organization_name" autocomplete="off" />
    </x-field>
</div>
<x-field name="headline" :for="$inputId('headline')" :bag="$errorBag" label="{{ __('Card headline') }}">
    <x-input name="headline" :id="$inputId('headline')" :bag="$errorBag" value="{{ $previous('headline', $current->headline ?? '') }}" placeholder="{{ __('For example: Join us to celebrate') }}" data-preview-input="headline" autocomplete="off" />
</x-field>
<x-field name="message" :for="$inputId('message')" :bag="$errorBag" label="{{ __('Message on the card') }}">
    <textarea id="{{ $inputId('message') }}" name="message" rows="3" class="form-control{{ $invalid('message') }}" placeholder="{{ __('For example: We would be delighted to celebrate this special day with you.') }}" data-preview-input="message" autocomplete="off">{{ $previous('message', $current->message ?? '') }}</textarea>
</x-field>
<p class="small text-body">{{ __('Leave the headline or message empty to use the wording from the template.') }}</p>
<div class="row">
    <div class="col-md-6">
        <x-field name="venue" :for="$inputId('venue')" :bag="$errorBag" label="{{ __('Venue') }}">
            <x-input name="venue" :id="$inputId('venue')" :bag="$errorBag" value="{{ $previous('venue', $current->venue ?? '') }}" placeholder="{{ __('For example: Serena Hotel, Arusha') }}" required data-preview-input="venue" autocomplete="off" />
        </x-field>
    </div>
    <div class="col-md-6">
        <x-field name="event_date" :for="$inputId('event_date')" :bag="$errorBag" label="{{ __('Date and time') }}">
            <x-input name="event_date" :id="$inputId('event_date')" :bag="$errorBag" type="datetime-local" value="{{ $previous('event_date', isset($current) ? $current->event_date->timezone(config('app.timezone'))->format('Y-m-d\TH:i') : '') }}" min="{{ now()->timezone(config('app.timezone'))->format('Y-m-d\T00:00') }}" required data-preview-input="event_date" autocomplete="off" />
        </x-field>
    </div>
</div>
<x-field name="dress_code" :for="$inputId('dress_code')" :bag="$errorBag" label="{{ __('Dress code') }}">
    <x-input name="dress_code" :id="$inputId('dress_code')" :bag="$errorBag" value="{{ $previous('dress_code', $current->dress_code ?? '') }}" placeholder="{{ __('For example: Formal or traditional attire') }}" data-preview-input="dress_code" autocomplete="off" />
</x-field>
@php($weddingOpen = $previous('type', $current?->type?->slug ?? '') === 'wedding')
<div class="border rounded-3 p-3 mb-3" data-wedding-lines @unless ($weddingOpen) hidden @endunless>
    <p class="fw-semibold mb-2">{{ __('Wedding lines on the card') }}</p>
    <p class="text-body">{{ __('These lines print in the card details when this event is a wedding.') }}</p>
    <div class="row">
        <div class="col-md-6">
            <x-field name="bride_name" :for="$inputId('bride_name')" :bag="$errorBag" label="{{ __('Bride’s name') }}">
                <x-input name="bride_name" :id="$inputId('bride_name')" :bag="$errorBag" value="{{ $previous('bride_name', $current?->bride_name ?? '') }}" />
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field name="groom_name" :for="$inputId('groom_name')" :bag="$errorBag" label="{{ __('Groom’s name') }}">
                <x-input name="groom_name" :id="$inputId('groom_name')" :bag="$errorBag" value="{{ $previous('groom_name', $current?->groom_name ?? '') }}" />
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field name="bride_family" :for="$inputId('bride_family')" :bag="$errorBag" label="{{ __('Bride’s family') }}">
                <x-input name="bride_family" :id="$inputId('bride_family')" :bag="$errorBag" value="{{ $previous('bride_family', $current?->bride_family ?? '') }}" />
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field name="groom_family" :for="$inputId('groom_family')" :bag="$errorBag" label="{{ __('Groom’s family') }}">
                <x-input name="groom_family" :id="$inputId('groom_family')" :bag="$errorBag" value="{{ $previous('groom_family', $current?->groom_family ?? '') }}" />
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field name="ceremony_place" :for="$inputId('ceremony_place')" :bag="$errorBag" label="{{ __('Where the vows happen') }}">
                <x-input name="ceremony_place" :id="$inputId('ceremony_place')" :bag="$errorBag" value="{{ $previous('ceremony_place', $current?->ceremony_place ?? '') }}" />
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field name="reception_venue" :for="$inputId('reception_venue')" :bag="$errorBag" label="{{ __('Reception venue') }}">
                <x-input name="reception_venue" :id="$inputId('reception_venue')" :bag="$errorBag" value="{{ $previous('reception_venue', $current?->reception_venue ?? '') }}" />
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field name="reception_time" :for="$inputId('reception_time')" :bag="$errorBag" label="{{ __('Reception time') }}">
                <x-input name="reception_time" :id="$inputId('reception_time')" :bag="$errorBag" value="{{ $previous('reception_time', $current?->reception_time ?? '') }}" />
            </x-field>
        </div>
        <div class="col-md-6">
            <x-field name="arrive_by" :for="$inputId('arrive_by')" :bag="$errorBag" label="{{ __('Guests should arrive by') }}">
                <x-input name="arrive_by" :id="$inputId('arrive_by')" :bag="$errorBag" value="{{ $previous('arrive_by', $current?->arrive_by ?? '') }}" />
            </x-field>
        </div>
    </div>
</div>
@once
    <style>
        .emca-dress-code-color {
            display: block;
            width: 2.5rem !important;
            min-width: 2.5rem !important;
            max-width: 2.5rem !important;
            height: 2.5rem !important;
            padding: 0.2rem;
            border: var(--bs-border-width, 1px) solid var(--bs-border-color, #dee2e6);
            border-radius: var(--bs-border-radius, 0.375rem);
            background-color: var(--bs-body-bg, #fff);
            cursor: pointer;
            flex: 0 0 2.5rem;
        }

        .emca-dress-code-color-row {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            max-width: 100%;
            margin-bottom: 0.5rem;
        }

        .emca-dress-code-color-row output {
            flex: 0 0 auto;
            margin: 0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            white-space: nowrap;
        }

        .emca-dress-code-color-row .btn {
            flex: 0 0 auto;
            margin-inline-start: 0;
        }

        .emca-template-search {
            position: relative;
        }

        .emca-template-search .form-control {
            padding-right: 2.75rem;
        }

        .emca-template-search-toggle {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 2.75rem;
            border: 0;
            background: transparent;
            color: var(--bs-secondary-color, #6c757d);
        }

        .emca-template-results {
            max-height: 16rem;
            margin-top: 0.25rem;
            overflow: auto;
            border: var(--bs-border-width, 1px) solid var(--bs-border-color, #dee2e6);
            border-radius: var(--bs-border-radius, 0.375rem);
            background: var(--bs-body-bg, #fff);
        }

        .emca-template-results[hidden] {
            display: none !important;
        }

        .emca-template-choice {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
            padding: 0.5rem 0.75rem;
            border-bottom: var(--bs-border-width, 1px) solid var(--bs-border-color, #dee2e6);
            cursor: pointer;
        }

        .emca-template-choice:last-child {
            border-bottom: 0;
        }

        .emca-template-choice:has(input:checked) {
            background: var(--bs-primary-bg-subtle, #e7f1ff);
        }
    </style>
@endonce
<div class="mb-3" data-dress-code-colors>
    <label class="form-label">{{ __('Dress code colors') }}</label>
    @error('dress_code_colors', $errorBag)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    <div data-dress-code-color-list>
        @foreach ($dressCodeColors as $colorIndex => $color)
            <div class="emca-dress-code-color-row" data-dress-code-color-row>
                <input class="emca-dress-code-color" type="color" id="{{ $inputId('dress_code_color_'.$colorIndex) }}" name="dress_code_colors[]" value="{{ $color }}" aria-label="{{ __('Dress code color') }}" data-dress-code-color-input>
                <output class="small text-body" data-dress-code-color-label>{{ $color }}</output>
                <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="{{ __('Remove color') }}" data-remove-dress-code-color>{{ __('Remove') }}</button>
            </div>
        @endforeach
    </div>
    <template data-dress-code-color-template>
        <div class="emca-dress-code-color-row" data-dress-code-color-row>
            <input class="emca-dress-code-color" type="color" id="{{ $inputId('dress_code_color_template') }}" name="dress_code_colors[]" value="#940000" aria-label="{{ __('Dress code color') }}" data-dress-code-color-input>
            <output class="small text-body" data-dress-code-color-label>#940000</output>
            <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="{{ __('Remove color') }}" data-remove-dress-code-color>{{ __('Remove') }}</button>
        </div>
    </template>
    <div>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-add-dress-code-color>
            <i class="fi fi-rr-plus me-1"></i>{{ __('Add a color') }}
        </button>
    </div>
    @foreach ($errors->getBag($errorBag)->getMessages() as $errorField => $messages)
        @if (str_starts_with($errorField, 'dress_code_colors.'))
            @foreach ($messages as $message)
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @endforeach
        @endif
    @endforeach
</div>
<div class="mb-3" data-template-picker>
    <label class="form-label" for="{{ $inputId('template_search') }}">{{ __('Template') }}</label>
    <div class="emca-template-search">
        <input
            type="search"
            id="{{ $inputId('template_search') }}"
            class="form-control"
            placeholder="{{ __('Search templates') }}"
            value="{{ $selectedTemplateName }}"
            autocomplete="off"
            data-template-select-search
            aria-controls="{{ $inputId('template_results') }}"
            aria-expanded="false"
            aria-autocomplete="list"
        >
        <button class="emca-template-search-toggle" type="button" data-template-menu-toggle aria-label="{{ __('Show templates') }}" aria-controls="{{ $inputId('template_results') }}" aria-expanded="false">
            <i class="fi fi-rr-angle-small-down" aria-hidden="true"></i>
        </button>
    </div>
    <div class="emca-template-results{{ $invalid('invitation_template_id') }}" id="{{ $inputId('template_results') }}" data-template-results hidden>
        <label class="emca-template-choice" data-template-none>
            <input type="radio" name="invitation_template_id" value="" @checked($selectedTemplateId === '')>
            <span>{{ __('No template yet') }}</span>
        </label>
        @foreach ($templates as $template)
            <label class="emca-template-choice" data-template-choice data-template-name="{{ strtolower($template->name.' '.($template->description ?? '')) }}">
                <input type="radio" name="invitation_template_id" value="{{ $template->id }}" @disabled(auth()->user()->isLead() && $template->host_only) @checked(! (auth()->user()->isLead() && $template->host_only) && $selectedTemplateId === (string) $template->id)>
                <span>
                    {{ $template->name }}
                    @if ($template->host_only)
                        <span class="text-body">· {{ __('For hosts') }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
    <p class="small text-body mt-2 mb-0" data-template-select-empty hidden>{{ __('No templates match that search.') }}</p>
    @error('invitation_template_id', $errorBag)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
@if (auth()->user()->isLead())
    <div class="alert alert-warning" role="status">
        {{ __('Publishing and the public list open after you become a host.') }}
        <a href="{{ route('become-host.create') }}">{{ __('Become a host') }}</a>
    </div>
@else
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="{{ $inputId('is_public') }}" name="is_public" value="1" @checked($errors->getBag($errorBag)->any() ? (bool) old('is_public') : ($current?->is_public ?? false))>
    <label class="form-check-label" for="{{ $inputId('is_public') }}">{{ __('List this event on the public upcoming page once it is published') }}</label>
</div>
<x-field name="status" :for="$inputId('status')" :bag="$errorBag" label="{{ __('Status') }}">
    <select id="{{ $inputId('status') }}" name="status" class="{{ $control }}{{ $invalid('status') }}">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected($previous('status', $current?->status->value ?? \App\EventStatus::Draft->value) === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
</x-field>
@endif
@once
    <script>
        document.addEventListener('change', (event) => {
            const select = event.target.closest('[data-preview-input="type"]');

            if (!select) {
                return;
            }

            const panel = select.closest('form')?.querySelector('[data-wedding-lines]');

            if (panel) {
                panel.hidden = select.value !== 'wedding';
            }
        });
    </script>
@endonce
