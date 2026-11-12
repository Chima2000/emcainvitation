@props([
    'methods' => \App\PaymentMethod::cases(),
    'selected' => 'mpesa',
    'error' => null,
])

<div class="row g-2 mb-4">
    @foreach ($methods as $methodOption)
        <div class="col-sm-6">
            <label class="d-flex gap-3 align-items-center border rounded-3 p-3 h-100 emca-method-option">
                <input class="form-check-input flex-shrink-0" type="radio" name="method" value="{{ $methodOption->value }}" @checked($selected === $methodOption->value) required>
                <span class="emca-method-logo">
                    <img src="{{ $methodOption->logo() }}" alt="{{ $methodOption->label() }}" loading="lazy" width="72" height="44">
                </span>
                <span class="min-w-0">
                    <span class="d-block fw-semibold">{{ $methodOption->label() }}</span>
                    <small class="d-block text-body text-break">{{ $methodOption->payTo() ?? __('Ask EmCa support for the account details.') }}</small>
                </span>
            </label>
        </div>
    @endforeach
    @if ($error)
        <div class="col-12"><div class="invalid-feedback d-block">{{ $error }}</div></div>
    @endif
</div>
