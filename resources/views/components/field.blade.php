@props(['name', 'label', 'for' => null, 'bag' => 'default'])

<div class="mb-3">
    <label class="form-label" for="{{ $for ?? $name }}">{{ $label }}</label>
    {{ $slot }}
    @error($name, $bag)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
