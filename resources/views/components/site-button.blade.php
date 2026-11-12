@props(['href' => null])

@php
    $class = 'theme-btn btn-style-one';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        <span class="btn-arrow-left"><i class="fa-solid fa-arrow-right"></i></span>
        <span class="btn-title">{{ $slot }}</span>
        <span class="btn-arrow-right"><i class="fa-solid fa-arrow-right"></i></span>
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $class]) }}>
        <span class="btn-arrow-left"><i class="fa-solid fa-arrow-right"></i></span>
        <span class="btn-title">{{ $slot }}</span>
        <span class="btn-arrow-right"><i class="fa-solid fa-arrow-right"></i></span>
    </button>
@endif
