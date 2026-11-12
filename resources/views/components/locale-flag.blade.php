@props([
    'code',
])

@php
    $clipId = 'emca-flag-'.str_replace('.', '', uniqid('', true));
@endphp

@if ($code === 'sw')
    <svg {{ $attributes->class('emca-locale-flag') }} width="28" height="28" viewBox="0 0 640 480" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <defs>
            <clipPath id="{{ $clipId }}">
                <path d="M10 0h160v120H10z"/>
            </clipPath>
        </defs>
        <g fill-rule="evenodd" clip-path="url(#{{ $clipId }})" transform="matrix(4 0 0 4 -40 0)">
            <path fill="#09f" d="M0 0h180v120H0z"/>
            <path fill="#090" d="M0 0h180L0 120z"/>
            <path fill="#000" d="M0 120h40l140-95V0h-40L0 95z"/>
            <path fill="#ff0" d="M0 91.5 137.2 0h13.5L0 100.5zM29.3 120 180 19.5v9L42.8 120z"/>
        </g>
    </svg>
@else
    <svg {{ $attributes->class('emca-locale-flag') }} width="28" height="28" viewBox="0 0 640 480" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <path fill="#012169" d="M0 0h640v480H0z"/>
        <path fill="#FFF" d="m75 0 244 181L562 0h78v62L400 241l240 178v61h-80L320 301 81 480H0v-60l239-178L0 64V0z"/>
        <path fill="#C8102E" d="m424 281 216 159v40L369 281zm-184 20 6 35L54 480H0zM640 0v3L391 191l2-44L590 0zM0 0l239 176h-60L0 42z"/>
        <path fill="#FFF" d="M241 0v480h160V0zM0 160v160h640V160z"/>
        <path fill="#C8102E" d="M0 193v96h640v-96zM273 0v480h96V0z"/>
    </svg>
@endif
