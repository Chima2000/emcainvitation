@props([
    'design' => \App\Models\InvitationTemplate::defaultDesign(),
    'kicker' => null,
    'title',
    'hosts',
    'headline' => null,
    'when',
    'venue',
    'message' => null,
    'dressCode' => null,
    'dressCodeColors' => [],
    'serial' => null,
    'qr' => null,
    'compact' => false,
])

@once
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ \App\CardFont::stylesheetUrl() }}">
    <link rel="stylesheet" href="{{ asset('css/invitation-card.css') }}?v={{ filemtime(public_path('css/invitation-card.css')) }}">
@endonce

<article
    {{ $attributes->class(['jp-card', 'jp-card--'.$design['layout']->value, 'jp-card--compact' => $compact]) }}
    style="--jp-paper: {{ $design['paper'] }}; --jp-ink: {{ $design['ink'] }}; --jp-accent: {{ $design['accent'] }}; --jp-font: {{ $design['font']->family() }};@if ($design['background_url']) --jp-bg: url('{{ $design['background_url'] }}');@endif"
>
    <div class="jp-card__frame" aria-hidden="true"></div>
    <div class="jp-card__body">
        <p class="jp-card__kicker" data-card-field="kicker">{{ $kicker ?? __('Invitation') }}</p>
        <div>
            <h2 class="jp-card__title" data-card-field="title">{{ $title }}</h2>
            <p class="jp-card__hosts" data-card-field="hosts">{{ $hosts }}</p>
        </div>
        <p class="jp-card__headline" data-card-field="headline">{{ $headline ?: __('You are invited') }}</p>
        <div class="jp-card__details">
            <p data-card-field="when">{{ $when }}</p>
            <p data-card-field="venue">{{ $venue }}</p>
            <p data-card-field="dress_code" @if (! $dressCode) hidden @endif>{{ $dressCode ? __('Dress: :code', ['code' => $dressCode]) : '' }}</p>
            <div class="jp-card__dress-code-colors" data-card-field="dress_code_colors" aria-label="{{ __('Dress code colors') }}" @if (! $dressCodeColors) hidden @endif>
                @foreach ($dressCodeColors as $color)
                    <span class="jp-card__color-swatch" style="background-color: {{ $color }};" title="{{ $color }}" aria-label="{{ $color }}"></span>
                @endforeach
            </div>
        </div>
        <p class="jp-card__message" data-card-field="message" @if (! $message) hidden @endif>{{ $message }}</p>
        @if ($qr || $serial)
            <div class="jp-card__footer">
                @if ($qr)
                    <div data-qr="{{ $qr }}" class="jp-card__qr"></div>
                @endif
                @if ($serial)
                    <p class="jp-card__serial">{{ $serial }}</p>
                @endif
            </div>
        @endif
    </div>
</article>
