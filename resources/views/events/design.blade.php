@extends('layouts.dashboard')

@section('title', __('Design the card'))
@section('heading', __('Design the card'))
@section('subheading')
    <a href="{{ route('events.show', $event) }}">{{ __('Back to :title', ['title' => $event->title]) }}</a>
@endsection

@php
    $when = $event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A');
    $templateData = $templates->mapWithKeys(fn ($template) => [$template->id => [
        'kicker' => $template->category->label(),
        'paper' => $template->paper,
        'ink' => $template->ink,
        'accent' => $template->accent,
        'font' => $template->font->value,
        'layout' => $template->layout->value,
        'background_url' => $template->backgroundUrl(),
        'headline' => $template->default_headline,
        'message' => $template->default_message,
    ]])->put('', [
        'kicker' => $event->type->label(),
        'paper' => $defaultDesign['paper'],
        'ink' => $defaultDesign['ink'],
        'accent' => $defaultDesign['accent'],
        'font' => $defaultDesign['font']->value,
        'layout' => $defaultDesign['layout']->value,
        'background_url' => null,
        'headline' => null,
        'message' => null,
    ]);
    $ownBackgroundUrl = ! empty($custom['background_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($custom['background_path']) : null;
    $selectedTemplateId = (string) old('invitation_template_id', $event->invitation_template_id ?? '');
@endphp

@section('content')
    <form method="POST" action="{{ route('events.design.update', $event) }}" enctype="multipart/form-data" data-card-designer>
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-xl-6 order-2 order-xl-1">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title h6 mb-1">{{ __('1. Start from a template') }}</h2>
                        <small class="text-body">{{ __('Templates are made by the Jumuika Pass team. Pick one, then change anything you like below.') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <label class="d-block border rounded-3 p-2 h-100 emca-design-choice">
                                    <input type="radio" class="visually-hidden" name="invitation_template_id" value="" @checked($selectedTemplateId === '')>
                                    <span class="d-flex align-items-center justify-content-center rounded-2 border text-center small text-body" style="min-height: 92px; background: {{ $defaultDesign['paper'] }};">{{ __('Plain card') }}</span>
                                    <span class="d-block small fw-semibold mt-2">{{ __('No template') }}</span>
                                </label>
                            </div>
                            @foreach ($templates as $template)
                                <div class="col-6 col-md-4">
                                    <label class="d-block border rounded-3 p-2 h-100 emca-design-choice">
                                        <input type="radio" class="visually-hidden" name="invitation_template_id" value="{{ $template->id }}" @checked($selectedTemplateId === (string) $template->id)>
                                        <span class="d-flex flex-column align-items-center justify-content-center rounded-2 border text-center px-2" style="min-height: 92px; background-color: {{ $template->paper }}; color: {{ $template->ink }}; border-color: {{ $template->accent }} !important; @if ($template->backgroundUrl()) background-image: linear-gradient(color-mix(in srgb, {{ $template->paper }} 80%, transparent), color-mix(in srgb, {{ $template->paper }} 80%, transparent)), url('{{ $template->backgroundUrl() }}'); background-size: cover; @endif font-family: {{ $template->font->family() }};">
                                            <small class="text-uppercase" style="color: {{ $template->accent }}; font-size: .6rem; letter-spacing: .15em;">{{ $template->category->label() }}</small>
                                            <span class="fw-semibold">{{ $template->name }}</span>
                                        </span>
                                        <span class="d-flex justify-content-between gap-1 small mt-2">
                                            <span class="fw-semibold text-truncate">{{ $template->name }}</span>
                                            <span class="text-body text-nowrap">{{ $template->price > 0 ? 'TZS '.number_format($template->price) : __('Free') }}</span>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('invitation_template_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <div>
                            <h2 class="card-title h6 mb-1">{{ __('2. Colours, font and layout') }}</h2>
                            <small class="text-body">{{ __('The preview changes as you choose.') }}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" data-designer-reset>
                            <i class="fi fi-rr-rotate-left me-1"></i> {{ __('Back to the template look') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            @foreach (['paper' => __('Paper colour'), 'ink' => __('Text colour'), 'accent' => __('Accent colour')] as $colorField => $colorLabel)
                                <div class="col-4">
                                    <label class="form-label" for="design-{{ $colorField }}">{{ $colorLabel }}</label>
                                    <input type="color" class="form-control form-control-color w-100 @error($colorField) is-invalid @enderror" id="design-{{ $colorField }}" name="{{ $colorField }}" value="{{ old($colorField, $design[$colorField]) }}" required>
                                    @error($colorField)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="design-font">{{ __('Font for names and headline') }}</label>
                            <select class="form-select @error('font') is-invalid @enderror" id="design-font" name="font">
                                @foreach ($fonts as $font)
                                    <option value="{{ $font->value }}" data-family="{{ $font->family() }}" style="font-family: {{ $font->family() }};" @selected(old('font', $design['font']->value) === $font->value)>{{ $font->label() }}</option>
                                @endforeach
                            </select>
                            @error('font')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <label class="form-label">{{ __('Layout') }}</label>
                        <div class="row g-2">
                            @foreach ($layouts as $layout)
                                <div class="col-sm-6">
                                    <label class="d-flex gap-2 border rounded-3 p-3 h-100 emca-design-choice">
                                        <input type="radio" class="form-check-input mt-1 flex-shrink-0" name="layout" value="{{ $layout->value }}" @checked(old('layout', $design['layout']->value) === $layout->value)>
                                        <span>
                                            <span class="d-block fw-semibold">{{ $layout->label() }}</span>
                                            <small class="text-body">{{ $layout->description() }}</small>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('layout')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title h6 mb-1">{{ __('3. Background picture') }}</h2>
                        <small class="text-body">{{ __('Optional. A photo of the couple, the venue or a pattern. JPG, PNG or WEBP up to 4 MB.') }}</small>
                    </div>
                    <div class="card-body">
                        @if ($ownBackgroundUrl)
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="{{ $ownBackgroundUrl }}" alt="" class="rounded-2 border" style="width: 72px; height: 72px; object-fit: cover;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="design-remove-background" name="remove_background" value="1" data-designer-remove-background>
                                    <label class="form-check-label" for="design-remove-background">{{ __('Remove my picture') }}</label>
                                </div>
                            </div>
                        @endif
                        <input type="file" class="form-control @error('background') is-invalid @enderror" name="background" accept="image/jpeg,image/png,image/webp" data-designer-background>
                        @error('background')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="design-hide-template-background" name="hide_template_background" value="1" data-designer-hide-template-background @checked(old('hide_template_background', ! empty($custom['hide_background'])))>
                            <label class="form-check-label" for="design-hide-template-background">{{ __('Do not use the template picture') }}</label>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title h6 mb-1">{{ __('4. Words on the card') }}</h2>
                        <small class="text-body">{{ __('Leave a field empty to use the template wording.') }}</small>
                    </div>
                    <div class="card-body">
                        <x-field name="headline" label="{{ __('Card headline') }}">
                            <x-input name="headline" value="{{ old('headline', $event->headline) }}" maxlength="160" data-designer-text="headline" />
                        </x-field>
                        <x-field name="message" label="{{ __('Message on the card') }}">
                            <textarea id="message" name="message" rows="4" maxlength="2000" class="form-control @error('message') is-invalid @enderror" data-designer-text="message">{{ old('message', $event->message) }}</textarea>
                        </x-field>
                        <x-field name="dress_code" label="{{ __('Dress code') }}">
                            <x-input name="dress_code" value="{{ old('dress_code', $event->dress_code) }}" maxlength="120" data-designer-text="dress_code" />
                        </x-field>
                        <small class="text-body d-block">{{ __('To change the title, names, date or venue, use Edit event.') }} <a href="{{ route('events.edit', $event) }}">{{ __('Edit event') }}</a></small>
                    </div>
                    <div class="card-footer d-flex flex-wrap gap-2 justify-content-end">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-light">{{ __('Cancel') }}</a>
                        <x-button><i class="fi fi-rr-disk me-1"></i> {{ __('Save design') }}</x-button>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 order-1 order-xl-2">
                <div class="emca-design-preview mb-4">
                    <p class="text-body small mb-2"><i class="fi fi-rr-eye me-1"></i> {{ __('Live preview. Each guest also sees their own QR code and card number.') }}</p>
                    <x-invitation-card
                        data-designer-preview
                        :design="$design"
                        :kicker="$event->cardKicker()"
                        :title="$event->title"
                        :hosts="$event->hostsLabel()"
                        :headline="$event->cardHeadline()"
                        :when="$when"
                        :venue="$event->venue"
                        :message="$event->cardMessage()"
                        :dress-code="$event->dress_code"
                        :dress-code-colors="$event->dress_code_colors ?? []"
                        serial="JP-0001"
                    />
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-card-designer]');
            const preview = document.querySelector('[data-designer-preview]');

            if (!form || !preview) {
                return;
            }

            const templates = @json($templateData);
            const ownBackground = @json($ownBackgroundUrl);
            const fallbackHeadline = @json(__('You are invited'));
            const dressPrefix = @json(__('Dress: :code', ['code' => '__CODE__']));
            const layouts = @json(collect($layouts)->map->value->values());
            let uploadedBackground = null;

            const field = (name) => form.querySelector(`[name="${name}"]`);
            const checked = (name) => form.querySelector(`[name="${name}"]:checked`);
            const currentTemplate = () => templates[checked('invitation_template_id')?.value ?? ''] ?? templates[''];
            const previewField = (name) => preview.querySelector(`[data-card-field="${name}"]`);

            const backgroundUrl = () => {
                if (uploadedBackground) {
                    return uploadedBackground;
                }

                const removeOwn = form.querySelector('[data-designer-remove-background]')?.checked;

                if (ownBackground && !removeOwn) {
                    return ownBackground;
                }

                if (form.querySelector('[data-designer-hide-template-background]')?.checked) {
                    return null;
                }

                return currentTemplate().background_url;
            };

            const render = () => {
                const template = currentTemplate();
                const font = field('font').selectedOptions[0];
                const layout = checked('layout')?.value ?? 'classic';
                const background = backgroundUrl();

                preview.style.setProperty('--jp-paper', field('paper').value);
                preview.style.setProperty('--jp-ink', field('ink').value);
                preview.style.setProperty('--jp-accent', field('accent').value);
                preview.style.setProperty('--jp-font', font?.dataset.family ?? '');
                preview.style.setProperty('--jp-bg', background ? `url("${background}")` : 'none');

                layouts.forEach((name) => preview.classList.toggle(`jp-card--${name}`, name === layout));

                previewField('kicker').textContent = template.kicker;
                previewField('headline').textContent = field('headline').value.trim() || template.headline || fallbackHeadline;

                const message = field('message').value.trim() || template.message || '';
                previewField('message').textContent = message;
                previewField('message').hidden = message === '';

                const dress = field('dress_code').value.trim();
                previewField('dress_code').textContent = dress ? dressPrefix.replace('__CODE__', dress) : '';
                previewField('dress_code').hidden = dress === '';
            };

            const applyTemplateLook = () => {
                const template = currentTemplate();

                field('paper').value = template.paper;
                field('ink').value = template.ink;
                field('accent').value = template.accent;
                field('font').value = template.font;
                form.querySelectorAll('[name="layout"]').forEach((radio) => {
                    radio.checked = radio.value === template.layout;
                });

                render();
            };

            form.querySelectorAll('[name="invitation_template_id"]').forEach((radio) => {
                radio.addEventListener('change', applyTemplateLook);
            });

            form.querySelector('[data-designer-reset]')?.addEventListener('click', applyTemplateLook);

            form.querySelector('[data-designer-background]')?.addEventListener('change', (event) => {
                const file = event.target.files?.[0];

                if (uploadedBackground) {
                    URL.revokeObjectURL(uploadedBackground);
                }

                uploadedBackground = file ? URL.createObjectURL(file) : null;
                render();
            });

            form.addEventListener('input', render);
            form.addEventListener('change', render);
            render();
        })();
    </script>
@endpush
