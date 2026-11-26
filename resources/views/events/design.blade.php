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
        'portrait_url' => $template->portraitUrl(),
        'portrait_slot' => $template->portraitSlot(),
        'scene' => $template->scene(),
        'headline' => $template->shownHeadline(),
        'message' => $template->shownMessage(),
    ]])->put('', [
        'kicker' => $event->type->label(),
        'paper' => $defaultDesign['paper'],
        'ink' => $defaultDesign['ink'],
        'accent' => $defaultDesign['accent'],
        'font' => $defaultDesign['font']->value,
        'layout' => $defaultDesign['layout']->value,
        'background_url' => null,
        'portrait_url' => null,
        'portrait_slot' => null,
        'scene' => null,
        'headline' => null,
        'message' => null,
    ]);
    $ownBackgroundUrl = ! empty($custom['background_path']) ? '/storage/'.ltrim(str_replace('\\', '/', $custom['background_path']), '/') : null;
    $ownPortraitUrl = ! empty($custom['portrait_path']) ? '/storage/'.ltrim(str_replace('\\', '/', $custom['portrait_path']), '/') : null;
    $selectedTemplateId = (string) old('invitation_template_id', $event->invitation_template_id ?? '');
    $templatePreviewLimit = 6;
    $orderedTemplates = $templates->values();
    $selectedTemplate = $orderedTemplates->first(fn ($template): bool => (string) $template->id === $selectedTemplateId);

    if ($selectedTemplate) {
        $orderedTemplates = $orderedTemplates
            ->reject(fn ($template): bool => $template->is($selectedTemplate))
            ->prepend($selectedTemplate)
            ->values();
    }

    $shownTemplates = $orderedTemplates->take($templatePreviewLimit);
    $moreTemplates = $orderedTemplates->slice($templatePreviewLimit)->values();
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
                        <label class="form-label" for="template-search">{{ __('Search templates') }}</label>
                        <input type="search" id="template-search" class="form-control mb-3" placeholder="{{ __('Search templates') }}" data-template-search>
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <label class="d-block border rounded-3 p-2 h-100 emca-design-choice">
                                    <input type="radio" class="visually-hidden" name="invitation_template_id" value="" @checked($selectedTemplateId === '')>
                                    <span class="d-flex align-items-center justify-content-center rounded-2 border text-center small text-body" style="min-height: 92px; background: {{ $defaultDesign['paper'] }};">{{ __('Plain card') }}</span>
                                    <span class="d-block small fw-semibold mt-2">{{ __('No template') }}</span>
                                </label>
                            </div>
                            @foreach ($shownTemplates as $template)
                                @include('events.partials.design-template-choice', ['template' => $template, 'selectedTemplateId' => $selectedTemplateId])
                            @endforeach
                        </div>
                        @if ($moreTemplates->isNotEmpty())
                            @include('partials.view-more', ['id' => 'design-template-more', 'open' => false])
                            <div class="row g-2 mt-1" id="design-template-more" hidden>
                                @foreach ($moreTemplates as $template)
                                    @include('events.partials.design-template-choice', ['template' => $template, 'selectedTemplateId' => $selectedTemplateId])
                                @endforeach
                            </div>
                        @endif
                        @error('invitation_template_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title h6 mb-1">{{ __('Design in Canva') }}</h2>
                        <small class="text-body">{{ __('You will be able to design this card in Canva and bring it back here. That connection is not open yet.') }}</small>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-light" disabled>{{ __('Open Canva') }}</button>
                    </div>
                </div>

                @if (auth()->user()->isLead())
                    <div class="alert alert-warning" role="status">
                        <strong class="d-block">{{ __('This stays locked during a trial') }}</strong>
                        {{ __('Custom colours, fonts and your own picture open after you become a host.') }}
                        <a href="{{ route('become-host.create') }}">{{ __('Become a host') }}</a>
                    </div>
                @else
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
                        <h2 class="card-title h6 mb-1">{{ __('Your photo') }}</h2>
                        <small class="text-body">{{ __('On a card with a photo frame, this picture replaces the sample and stays in that same place. JPG, PNG or WEBP up to 4 MB.') }}</small>
                    </div>
                    <div class="card-body">
                        @if ($ownPortraitUrl)
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="{{ $ownPortraitUrl }}" alt="" class="rounded-2 border" style="width: 72px; height: 72px; object-fit: cover;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="design-remove-portrait" name="remove_portrait" value="1" data-designer-remove-portrait>
                                    <label class="form-check-label" for="design-remove-portrait">{{ __('Use the sample photo') }}</label>
                                </div>
                            </div>
                        @endif
                        <input type="file" class="form-control @error('portrait') is-invalid @enderror" name="portrait" accept="image/jpeg,image/png,image/webp" data-designer-portrait>
                        @error('portrait')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                @endif

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
                        :lines="$event->weddingCardLines()"
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
            const ownPortrait = @json($ownPortraitUrl);
            const fallbackHeadline = @json(__('You are invited'));
            const dressPrefix = @json(__('Dress: :code', ['code' => '__CODE__']));
            const layouts = @json(collect($layouts)->map->value->values());
            let uploadedBackground = null;
            let uploadedPortrait = null;

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

            const portraitUrl = () => {
                if (uploadedPortrait) {
                    return uploadedPortrait;
                }

                if (form.querySelector('[data-designer-remove-portrait]')?.checked) {
                    return currentTemplate().portrait_url;
                }

                return ownPortrait || currentTemplate().portrait_url;
            };

            const applyPortrait = (template) => {
                const photo = preview.querySelector('[data-card-photo]');
                const background = backgroundUrl();
                const usingTemplateArt = background === template.background_url;
                const slot = usingTemplateArt ? template.portrait_slot : null;
                const url = slot ? portraitUrl() : null;
                const show = Boolean(slot && url);

                preview.classList.toggle('jp-card--has-photo', show);
                [...preview.classList].filter((name) => name === 'jp-card--scene' || name.startsWith('jp-card--scene-')).forEach((name) => preview.classList.remove(name));

                if (usingTemplateArt && template.scene) {
                    preview.classList.add('jp-card--scene', `jp-card--scene-${template.scene}`);
                }

                if (!photo) {
                    return;
                }

                photo.hidden = !show;
                photo.classList.remove('jp-card__photo--arch', 'jp-card__photo--circle', 'jp-card__photo--frame');
                photo.classList.add(`jp-card__photo--${slot?.shape || 'frame'}`);
                ['top', 'left', 'width', 'height', 'radius', 'clip'].forEach((key) => {
                    const value = slot?.box?.[key];

                    if (value) {
                        photo.style.setProperty(`--photo-${key}`, value);
                    } else {
                        photo.style.removeProperty(`--photo-${key}`);
                    }
                });

                if (slot?.box?.copy) {
                    preview.style.setProperty('--photo-copy', slot.box.copy);
                } else {
                    preview.style.removeProperty('--photo-copy');
                }

                if (show) {
                    photo.src = url;
                }
            };

            const render = () => {
                const template = currentTemplate();
                const font = field('font')?.selectedOptions[0];
                const layout = checked('layout')?.value ?? template.layout ?? 'classic';
                const background = backgroundUrl();

                if (field('paper')) {
                    preview.style.setProperty('--jp-paper', field('paper').value);
                    preview.style.setProperty('--jp-ink', field('ink').value);
                    preview.style.setProperty('--jp-accent', field('accent').value);
                    preview.style.setProperty('--jp-font', font?.dataset.family ?? '');
                }
                preview.style.setProperty('--jp-bg', background ? `url("${background}")` : 'none');
                applyPortrait(template);

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

                if (field('paper')) {
                    field('paper').value = template.paper;
                    field('ink').value = template.ink;
                    field('accent').value = template.accent;
                    field('font').value = template.font;
                }
                form.querySelectorAll('[name="layout"]').forEach((radio) => {
                    radio.checked = radio.value === template.layout;
                });

                render();
            };

            form.querySelectorAll('[name="invitation_template_id"]').forEach((radio) => {
                radio.addEventListener('change', applyTemplateLook);
            });

            form.querySelector('[data-designer-reset]')?.addEventListener('click', applyTemplateLook);

            form.querySelector('[data-template-search]')?.addEventListener('input', (event) => {
                const term = event.target.value.trim().toLowerCase();
                const more = document.getElementById('design-template-more');
                const moreButton = form.querySelector('[aria-controls="design-template-more"]');

                if (more && moreButton) {
                    moreButton.hidden = term !== '';
                    more.hidden = term === '' && moreButton.getAttribute('aria-expanded') !== 'true';
                }

                form.querySelectorAll('[data-template-choice]').forEach((choice) => {
                    const name = choice.dataset.templateName ?? '';
                    choice.hidden = term !== '' && !name.includes(term);
                });
            });

            form.querySelector('[data-designer-background]')?.addEventListener('change', (event) => {
                const file = event.target.files?.[0];

                if (uploadedBackground) {
                    URL.revokeObjectURL(uploadedBackground);
                }

                uploadedBackground = file ? URL.createObjectURL(file) : null;
                render();
            });

            form.querySelector('[data-designer-portrait]')?.addEventListener('change', (event) => {
                const file = event.target.files?.[0];

                if (uploadedPortrait) {
                    URL.revokeObjectURL(uploadedPortrait);
                }

                uploadedPortrait = file ? URL.createObjectURL(file) : null;
                render();
            });

            form.addEventListener('input', render);
            form.addEventListener('change', render);
            render();
        })();
    </script>
@endpush
