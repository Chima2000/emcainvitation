@props([
    'tone' => 'dashboard',
])

@php
    $current = app()->getLocale();
    $shortCodes = [
        'sw' => 'SW',
        'en' => 'EN',
    ];
    $currentShort = $shortCodes[$current] ?? strtoupper($current);
@endphp

@if ($tone === 'marketing')
    <details
        {{ $attributes->class(['emca-locale']) }}
    >
        <summary class="emca-locale-toggle" aria-label="{{ __('Language') }}: {{ $currentShort }}">
            <x-locale-flag :code="$current" />
            <span class="emca-locale-code">{{ $currentShort }}</span>
        </summary>
        <div class="emca-locale-menu" role="group" aria-label="{{ __('Language') }}">
            @foreach (config('emca.locales') as $code => $label)
                <form method="POST" action="{{ route('locale.update', $code) }}" data-emca-native>
                    @csrf
                    <button type="submit" @if ($current === $code) aria-current="true" @endif>
                        <span class="emca-locale-choice">
                            <x-locale-flag :code="$code" />
                            <span>{{ $shortCodes[$code] ?? strtoupper($code) }}</span>
                        </span>
                        <span>{{ $label }}</span>
                    </button>
                </form>
            @endforeach
        </div>
    </details>
@else
    <div class="dropdown" {{ $attributes }}>
        <button class="btn btn-sm btn-light dropdown-toggle d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Language') }}: {{ $currentShort }}">
            <x-locale-flag :code="$current" />
            {{ $currentShort }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            @foreach (config('emca.locales') as $code => $label)
                <li>
                    <form method="POST" action="{{ route('locale.update', $code) }}" data-emca-native>
                        @csrf
                        <button class="dropdown-item d-flex align-items-center gap-2" type="submit" @if ($current === $code) aria-current="true" @endif>
                            <x-locale-flag :code="$code" />
                            <span>{{ $shortCodes[$code] ?? strtoupper($code) }}</span>
                            <span class="text-muted">{{ $label }}</span>
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    </div>
@endif
