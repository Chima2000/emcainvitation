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
    'lines' => [],
    'guestName' => null,
    'serial' => null,
    'qr' => null,
    'compact' => false,
])

@php
    $portraitSlot = $design['portrait_slot'] ?? null;
    $portraitUrl = $design['portrait_url'] ?? null;
    $showsPortrait = is_array($portraitSlot) && filled($portraitUrl);
    $photoBox = is_array($portraitSlot) ? ($portraitSlot['box'] ?? null) : null;
    $scene = is_string($design['scene'] ?? null) && $design['scene'] !== '' ? $design['scene'] : null;
@endphp

@once
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ \App\CardFont::stylesheetUrl() }}">
    <link rel="stylesheet" href="{{ asset('css/invitation-card.css') }}?v={{ filemtime(public_path('css/invitation-card.css')) }}">
@endonce

<article
    {{ $attributes->class(['jp-card', 'jp-card--'.$design['layout']->value, 'jp-card--compact' => $compact, 'jp-card--has-photo' => $showsPortrait, 'jp-card--scene' => $scene !== null, 'jp-card--scene-'.$scene => $scene !== null]) }}
    style="--jp-paper: {{ $design['paper'] }}; --jp-ink: {{ $design['ink'] }}; --jp-accent: {{ $design['accent'] }}; --jp-font: {{ $design['font']->family() }};@if ($design['background_url']) --jp-bg: url('{{ $design['background_url'] }}');@endif @if (is_array($photoBox)) --photo-copy: {{ $photoBox['copy'] }};@endif"
>
    <div class="jp-card__frame" aria-hidden="true"></div>
    <svg class="jp-card__bloom jp-card__bloom--north" viewBox="48 0 74 62" aria-hidden="true">
        <path d="M96 18c-8 10-6 22 2 28-12-2-22 2-28 10 2-12 8-20 16-26-10 2-20-2-26-10 10 0 18-2 26-6 2 6 6 8 10 4z" />
        <path d="M78 8c2 14 12 22 24 24-14 2-22 12-24 24-2-14-12-22-24-24 14-2 22-12 24-24z" />
        <path d="M108 36c-6 8-4 16 2 20-8 0-14 4-18 10 2-8 6-14 12-16-6 2-12 0-16-4 6 0 12-2 16-6 1 4 3 6 4-4z" />
    </svg>
    <svg class="jp-card__bloom jp-card__bloom--south" viewBox="-2 58 74 66" aria-hidden="true">
        <path d="M24 102c8-10 6-22-2-28 12 2 22-2 28-10-2 12-8 20-16 26 10-2 20 2 26 10-10 0-18 2-26 6-2-6-6-8-10-4z" />
        <path d="M42 112c-2-14-12-22-24-24 14-2 22-12 24-24 2 14 12 22 24 24-14 2-22 12-24 24z" />
        <path d="M12 84c6-8 4-16-2-20 8 0 14-4 18-10-2 8-6 14-12 16 6-2 12 0 16 4-6 0-12 2-16 6-1-4-3-6-4 4z" />
    </svg>
    <img
        data-card-photo
        class="jp-card__photo jp-card__photo--{{ $portraitSlot['shape'] ?? 'frame' }}"
        alt=""
        @if (is_array($photoBox)) style="--photo-top: {{ $photoBox['top'] }}; --photo-left: {{ $photoBox['left'] }}; --photo-width: {{ $photoBox['width'] }}; --photo-height: {{ $photoBox['height'] }}; --photo-radius: {{ $photoBox['radius'] }}; --photo-clip: {{ $photoBox['clip'] }};" @endif
        @if ($portraitUrl) src="{{ $portraitUrl }}" @endif
        @unless ($showsPortrait) hidden @endunless
    >
    <div class="jp-card__body">
        <p class="jp-card__kicker" data-card-field="kicker">{{ $kicker ?? __('Invitation') }}</p>
        <p class="jp-card__hosts" data-card-field="hosts">{{ $hosts }}</p>
        <p class="jp-card__guest" data-card-field="guest" @if (! $guestName) hidden @endif>{{ $guestName }}</p>
        <h2 class="jp-card__title" data-card-field="title"@if (blank($title)) hidden @endif>{{ $title }}</h2>
        <div class="jp-card__details">
            <p data-card-field="when"@if (blank($when)) hidden @endif>{{ $when }}</p>
            <p data-card-field="venue"@if (blank($venue)) hidden @endif>{{ $venue }}</p>
            <p data-card-field="dress_code" @if (! $dressCode) hidden @endif>{{ $dressCode ? __('Dress: :code', ['code' => $dressCode]) : '' }}</p>
            <div data-card-lines>
                @foreach ($lines as $line)
                    <p>{{ $line }}</p>
                @endforeach
            </div>
            <div class="jp-card__dress-code-colors" data-card-field="dress_code_colors" aria-label="{{ __('Dress code colors') }}" @if (! $dressCodeColors) hidden @endif>
                @foreach ($dressCodeColors as $color)
                    <span class="jp-card__color-swatch" style="background-color: {{ $color }};" title="{{ $color }}" aria-label="{{ $color }}"></span>
                @endforeach
            </div>
        </div>
        <p class="jp-card__message" data-card-field="message" @if (! $message) hidden @endif>{{ $message }}</p>
        <p class="jp-card__headline" data-card-field="headline">{{ $headline ?: __('You are invited') }}</p>
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
