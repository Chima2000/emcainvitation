@props([
    'name',
    'id' => null,
])

@php
    $fieldId = $id ?? $name;
@endphp

<div class="jumuika-phone">
    <span class="jumuika-phone-flag">
        <x-locale-flag code="sw" />
    </span>
    <input
        type="tel"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        {{ $attributes->merge(['class' => 'form-control jumuika-phone-input'.($errors->has($name) ? ' is-invalid' : '')]) }}
    >
</div>
