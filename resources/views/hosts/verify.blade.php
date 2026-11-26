@extends('layouts.dashboard')

@section('title', __('Verify your phone'))
@section('heading', __('Verify your phone'))
@section('subheading', __('We sent a code to :phone. Enter it to become a host.', ['phone' => \App\Support\TanzanianPhone::toLocal($phone)]))

@section('content')
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('become-host.verify.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="code">{{ __('SMS code') }}</label>
                            <input type="text" class="form-control text-center fs-4 fw-semibold @error('code') is-invalid @enderror" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" placeholder="000000" required autofocus>
                            @error('code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <p class="form-text mb-0">{{ __('The code expires in :minutes minutes.', ['minutes' => $minutes]) }}</p>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Verify and become a host') }}</button>
                    </form>
                    <form method="POST" action="{{ route('become-host.verify.resend') }}" class="mt-3">
                        @csrf
                        <span class="form-text">{{ __('No SMS yet?') }}</span>
                        <button type="submit" class="btn btn-link p-0 align-baseline">{{ __('Send a new code') }}</button>
                    </form>
                    <p class="mb-0"><a href="{{ route('become-host.create') }}">{{ __('Use a different number') }}</a></p>
                </div>
            </div>
        </div>
    </div>
@endsection
