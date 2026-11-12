@props(['variant' => 'link'])

@php
    $class = match ($variant) {
        'primary' => 'btn btn-primary waves-effect waves-light',
        'menu' => 'menu-link',
        'dropdown' => 'dropdown-item d-flex align-items-center gap-2',
        'quiet' => 'text-dark fw-semibold',
        default => '',
    };
@endphp

@if ($variant === 'primary')
    <button type="button" data-bs-toggle="modal" data-bs-target="#createEventModal" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@else
    <a href="javascript:void(0);" role="button" data-bs-toggle="modal" data-bs-target="#createEventModal" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@endif
