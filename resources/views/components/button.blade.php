@props(['href' => null, 'variant' => 'primary'])

@php
    $variants = [
        'primary' => 'btn btn-primary waves-effect waves-light',
        'secondary' => 'btn btn-light waves-effect waves-light',
        'ghost' => 'btn btn-link',
        'danger' => 'btn btn-danger waves-effect waves-light',
    ];
    $class = $variants[$variant] ?? $variants['primary'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $class]) }}>{{ $slot }}</button>
@endif
