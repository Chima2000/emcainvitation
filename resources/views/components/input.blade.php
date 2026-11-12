@props(['name', 'bag' => 'default'])

<input
    name="{{ $name }}"
    id="{{ $attributes->get('id', $name) }}"
    {{ $attributes->except('id')->merge(['class' => 'form-control'.($errors->getBag($bag)->has($name) ? ' is-invalid' : '')]) }}
>
