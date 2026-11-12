@php
    $control = 'form-select';
    $current = $event ?? null;
    $fieldId = $fieldId ?? '';
    $errorBag = $errorBag ?? 'default';
    $inputId = fn (string $name): string => $fieldId.$name;
    $invalid = fn (string $name): string => $errors->getBag($errorBag)->has($name) ? ' is-invalid' : '';
    $hostNameIsOrganization = (bool) old('host_name_is_organization', $current?->host_name_is_organization ?? false);
    $dressCodeColors = old('dress_code_colors', $current?->dress_code_colors ?? []);
    $dressCodeColors = is_array($dressCodeColors) ? $dressCodeColors : [];
@endphp

<x-field name="title" :for="$inputId('title')" :bag="$errorBag" label="{{ __('Event title') }}">
    <x-input name="title" :id="$inputId('title')" :bag="$errorBag" value="{{ old('title', $current->title ?? '') }}" placeholder="{{ __('For example: Asha and Juma’s wedding') }}" required data-preview-input="title" />
</x-field>
<x-field name="type" :for="$inputId('type')" :bag="$errorBag" label="{{ __('Type') }}">
    <select id="{{ $inputId('type') }}" name="type" class="{{ $control }}{{ $invalid('type') }}" required data-preview-input="type">
        @php($typeOptions = $current?->type && ! $types->contains('slug', $current->type->slug) ? $types->concat([$current->type]) : $types)
        @foreach ($typeOptions as $type)
            <option value="{{ $type->slug }}" @selected(old('type', $current?->type?->slug ?? ($types->first()?->slug ?? '')) === $type->slug)>{{ $type->label() }}</option>
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
            <x-input name="partner_name" :id="$inputId('partner_name')" :bag="$errorBag" value="{{ old('partner_name', $current->partner_name ?? '') }}" placeholder="{{ __('For example: Juma') }}" data-preview-input="partner_name" />
        </x-field>
    </div>
</div>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="{{ $inputId('host_name_is_organization') }}" name="host_name_is_organization" value="1" @checked($hostNameIsOrganization) data-organization-host-toggle>
    <label class="form-check-label" for="{{ $inputId('host_name_is_organization') }}">{{ __('Use a company or organisation name on the invitation') }}</label>
</div>
<div class="mb-3" data-organization-host-panel @if (! $hostNameIsOrganization) hidden @endif>
    <x-field name="organization_name" :for="$inputId('organization_name')" :bag="$errorBag" label="{{ __('Company or organisation name') }}">
        <x-input name="organization_name" :id="$inputId('organization_name')" :bag="$errorBag" value="{{ old('organization_name', $hostNameIsOrganization ? ($current->host_name ?? '') : '') }}" placeholder="{{ __('For example: Upendo Foundation') }}" :disabled="! $hostNameIsOrganization" data-preview-input="organization_name" />
    </x-field>
</div>
<x-field name="headline" :for="$inputId('headline')" :bag="$errorBag" label="{{ __('Card headline') }}">
    <x-input name="headline" :id="$inputId('headline')" :bag="$errorBag" value="{{ old('headline', $current->headline ?? '') }}" placeholder="{{ __('For example: Join us to celebrate') }}" data-preview-input="headline" />
</x-field>
<x-field name="message" :for="$inputId('message')" :bag="$errorBag" label="{{ __('Message on the card') }}">
    <textarea id="{{ $inputId('message') }}" name="message" rows="3" class="form-control{{ $invalid('message') }}" placeholder="{{ __('For example: We would be delighted to celebrate this special day with you.') }}" data-preview-input="message">{{ old('message', $current->message ?? '') }}</textarea>
</x-field>
<div class="row">
    <div class="col-md-6">
        <x-field name="venue" :for="$inputId('venue')" :bag="$errorBag" label="{{ __('Venue') }}">
            <x-input name="venue" :id="$inputId('venue')" :bag="$errorBag" value="{{ old('venue', $current->venue ?? '') }}" placeholder="{{ __('For example: Serena Hotel, Arusha') }}" required data-preview-input="venue" />
        </x-field>
    </div>
    <div class="col-md-6">
        <x-field name="event_date" :for="$inputId('event_date')" :bag="$errorBag" label="{{ __('Date and time') }}">
            <x-input name="event_date" :id="$inputId('event_date')" :bag="$errorBag" type="datetime-local" value="{{ old('event_date', isset($current) ? $current->event_date->format('Y-m-d\TH:i') : '') }}" required data-preview-input="event_date" />
        </x-field>
    </div>
</div>
<x-field name="dress_code" :for="$inputId('dress_code')" :bag="$errorBag" label="{{ __('Dress code') }}">
    <x-input name="dress_code" :id="$inputId('dress_code')" :bag="$errorBag" value="{{ old('dress_code', $current->dress_code ?? '') }}" placeholder="{{ __('For example: Formal or traditional attire') }}" data-preview-input="dress_code" />
</x-field>
<div class="mb-3" data-dress-code-colors>
    <label class="form-label">{{ __('Dress code colors') }}</label>
    @error('dress_code_colors', $errorBag)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    <div data-dress-code-color-list>
        @foreach ($dressCodeColors as $colorIndex => $color)
            <div class="d-flex align-items-center gap-2 mb-2" data-dress-code-color-row>
                <input class="form-control form-control-color" type="color" id="{{ $inputId('dress_code_color_'.$colorIndex) }}" name="dress_code_colors[]" value="{{ $color }}" aria-label="{{ __('Dress code color') }}" data-dress-code-color-input>
                <output class="small text-body" data-dress-code-color-label>{{ $color }}</output>
                <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="{{ __('Remove color') }}" data-remove-dress-code-color>{{ __('Remove') }}</button>
            </div>
        @endforeach
    </div>
    <template data-dress-code-color-template>
        <div class="d-flex align-items-center gap-2 mb-2" data-dress-code-color-row>
            <input class="form-control form-control-color" type="color" id="{{ $inputId('dress_code_color_template') }}" name="dress_code_colors[]" value="#940000" aria-label="{{ __('Dress code color') }}" data-dress-code-color-input>
            <output class="small text-body" data-dress-code-color-label>#940000</output>
            <button class="btn btn-sm btn-outline-secondary" type="button" aria-label="{{ __('Remove color') }}" data-remove-dress-code-color>{{ __('Remove') }}</button>
        </div>
    </template>
    <button class="btn btn-sm btn-outline-secondary" type="button" data-add-dress-code-color>
        <i class="fi fi-rr-plus me-1"></i>{{ __('Add a color') }}
    </button>
    @foreach ($errors->getBag($errorBag)->getMessages() as $errorField => $messages)
        @if (str_starts_with($errorField, 'dress_code_colors.'))
            @foreach ($messages as $message)
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @endforeach
        @endif
    @endforeach
</div>
<x-field name="invitation_template_id" :for="$inputId('invitation_template_id')" :bag="$errorBag" label="{{ __('Template') }}">
    <select id="{{ $inputId('invitation_template_id') }}" name="invitation_template_id" class="{{ $control }}{{ $invalid('invitation_template_id') }}">
        <option value="">{{ __('No template yet') }}</option>
        @foreach ($templates as $template)
            <option value="{{ $template->id }}" @selected((string) old('invitation_template_id', $current->invitation_template_id ?? ($selectedTemplate ?? '')) === (string) $template->id)>{{ $template->name }}</option>
        @endforeach
    </select>
</x-field>
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="{{ $inputId('is_public') }}" name="is_public" value="1" @checked($errors->getBag($errorBag)->any() ? (bool) old('is_public') : ($current->is_public ?? false))>
    <label class="form-check-label" for="{{ $inputId('is_public') }}">{{ __('List this event on the public upcoming page once it is published') }}</label>
</div>
<x-field name="status" :for="$inputId('status')" :bag="$errorBag" label="{{ __('Status') }}">
    <select id="{{ $inputId('status') }}" name="status" class="{{ $control }}{{ $invalid('status') }}">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $current->status->value ?? \App\EventStatus::Draft->value) === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
</x-field>
