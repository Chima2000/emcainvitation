@props([
    'name',
    'id' => null,
])

@php
    $fieldId = $id ?? $name;
@endphp

<div class="jumuika-password" data-password-field>
    <input
        type="password"
        name="{{ $name }}"
        id="{{ $fieldId }}"
        {{ $attributes->merge(['class' => 'form-control jumuika-password-input'.($errors->has($name) ? ' is-invalid' : '')]) }}
    >
    <button
        type="button"
        class="jumuika-password-toggle"
        data-password-toggle
        data-label-show="{{ __('Show password') }}"
        data-label-hide="{{ __('Hide password') }}"
        aria-pressed="false"
        aria-label="{{ __('Show password') }}"
    >
        <i class="fi fi-rr-eye" data-password-icon="show" aria-hidden="true"></i>
        <i class="fi fi-rr-eye-crossed" data-password-icon="hide" aria-hidden="true"></i>
    </button>
</div>

@pushOnce('scripts')
    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-password-toggle]');

            if (!button) {
                return;
            }

            const field = button.closest('[data-password-field]');
            const input = field ? field.querySelector('input') : null;

            if (!input) {
                return;
            }

            const showing = input.type === 'password';

            input.type = showing ? 'text' : 'password';
            button.setAttribute('aria-pressed', showing ? 'true' : 'false');
            button.setAttribute('aria-label', showing ? button.dataset.labelHide : button.dataset.labelShow);
            field.classList.toggle('is-visible', showing);
        });
    </script>
@endpushOnce
