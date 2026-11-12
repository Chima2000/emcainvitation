@extends('layouts.admin')

@section('title', __('Message prices'))
@section('heading', __('Message prices'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Catalog') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Message prices') }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.packages.index') }}" class="btn btn-outline-light btn-shadow waves-effect">
        <i class="fi fi-rr-box-open me-1"></i> {{ __('Packages') }}
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-8">
            <form method="POST" action="{{ route('admin.pricing.update') }}" class="card">
                @csrf
                @method('PUT')
                <div class="card-header">
                    <h6 class="card-title mb-1">{{ __('Pay-as-you-go rates') }}</h6>
                    <small class="text-body">{{ __('Hosts without a package, or who go past their package, are charged these amounts from their wallet.') }}</small>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($items as $item)
                        <div class="list-group-item py-3">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-7">
                                    <label class="fw-semibold d-block" for="rate-{{ $item->value }}">{{ $item->label() }}</label>
                                    <small class="text-body">{{ $item->description() }}</small>
                                    @isset($market[$item->value])
                                        <small class="d-block text-body">{{ __('Market average: TZS :amount', ['amount' => number_format($market[$item->value])]) }}</small>
                                    @endisset
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="text" inputmode="numeric" autocomplete="off" data-emca-money class="form-control @error('rates.'.$item->value) is-invalid @enderror" id="rate-{{ $item->value }}" name="rates[{{ $item->value }}]" value="{{ old('rates.'.$item->value, $rates[$item->value]) }}" required>
                                        @error('rates.'.$item->value)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <x-button>{{ __('Save prices') }}</x-button>
                </div>
            </form>
        </div>
        <div class="col-xl-4">
            <div class="card emca-hero-card">
                <div class="card-body">
                    <span class="emca-card-label">{{ __('One guest, pay as you go') }}</span>
                    <strong class="d-block fs-3 fw-bold">TZS {{ number_format($rates['card'] + $rates['whatsapp']) }}</strong>
                    <small class="emca-card-note">{{ __('Card + WhatsApp delivery') }}</small>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">{{ __('How charging works') }}</h6>
                    <ul class="emca-check-list mb-0">
                        <li>{{ __('An invitation costs the card rate plus the channel rate.') }}</li>
                        <li>{{ __('Reminders and thank-you messages use their own rate.') }}</li>
                        <li>{{ __('Messages covered by a package cost nothing extra.') }}</li>
                        <li>{{ __('Sending pauses when the wallet cannot cover the next message.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
