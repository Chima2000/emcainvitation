@extends('layouts.dashboard')

@section('title', __('Edit :title', ['title' => $event->title]))
@section('heading', __('Edit event'))
@section('subheading')
    <a href="{{ route('events.show', $event) }}">{{ __('Back to :title', ['title' => $event->title]) }}</a>
@endsection

@php
    $previewTemplates = $templates->mapWithKeys(fn ($template) => [(string) $template->id => $template->previewPayload()])->all();
    $defaultDesign = \App\Models\InvitationTemplate::defaultDesign();
    $previewDefaultDesign = [
        'paper' => $defaultDesign['paper'],
        'ink' => $defaultDesign['ink'],
        'accent' => $defaultDesign['accent'],
        'fontFamily' => $defaultDesign['font']->family(),
        'layout' => $defaultDesign['layout']->value,
    ];
    $previewTemplate = $templates->firstWhere('id', $event->invitation_template_id);
@endphp

@section('content')
    <form method="POST" action="{{ route('events.update', $event) }}" data-event-form data-event-preview-form data-event-field-prefix="edit-event-"
        data-preview-templates="{{ json_encode((object) $previewTemplates, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
        data-preview-default="{{ json_encode($previewDefaultDesign, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
        data-preview-invite-headline="{{ __('You are invited') }}"
        data-preview-dress-prefix="{{ __('Dress') }}"
        data-preview-bride-family="{{ __('Bride’s family: :name', ['name' => '__VALUE__']) }}"
        data-preview-groom-family="{{ __('Groom’s family: :name', ['name' => '__VALUE__']) }}"
        data-preview-vows="{{ __('Vows at :place', ['place' => '__VALUE__']) }}"
        data-preview-reception="{{ __('Reception at :place', ['place' => '__VALUE__']) }}"
        data-preview-reception-time="{{ __('Reception time: :time', ['time' => '__VALUE__']) }}"
        data-preview-arrive="{{ __('Arrive by :time', ['time' => '__VALUE__']) }}">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        @include('events.partials.fields', ['fieldId' => 'edit-event-'])
                        <x-button>{{ __('Save details') }}</x-button>
                    </div>
                </div>
            </div>
            <aside class="col-lg-5">
                <div class="position-sticky top-0">
                    <h2 class="h6 mb-3">{{ __('Live invitation preview') }}</h2>
                    <x-invitation-card
                        :design="$event->cardDesign()"
                        :kicker="$previewTemplate?->category->label() ?? $event->type->label()"
                        :title="$event->title"
                        :hosts="$event->hostsLabel()"
                        :headline="$event->cardHeadline()"
                        :when="$event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A')"
                        :venue="$event->venue"
                        :message="$event->cardMessage()"
                        :dress-code="$event->dress_code"
                        :dress-code-colors="$event->dress_code_colors ?? []"
                        :lines="$event->weddingCardLines()"
                        compact
                        data-preview-card
                    />
                    <p class="small text-body mt-3 mb-0">{{ __('This is the card for this event. An empty headline or message keeps the wording from the template.') }}</p>
                </div>
            </aside>
        </div>
    </form>
    <form class="mt-3" method="POST" action="{{ route('events.destroy', $event) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Delete this event and its guest list?')) }})">
        @csrf
        @method('DELETE')
        <x-button variant="danger">{{ __('Delete event') }}</x-button>
    </form>
@endsection
