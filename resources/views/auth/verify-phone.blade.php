@extends('layouts.auth')

@section('title', __('Verify your phone').' | '.config('app.name'))
@section('heading', __('Verify your phone'))
@section('subheading', __('We sent a 6-digit code by SMS to :phone. Enter it to activate your account.', ['phone' => \App\Support\TanzanianPhone::toLocal($phone)]))

@section('content')
    <form method="POST" action="{{ route('register.verify') }}">
        @csrf
        <div class="mb-4">
            <label class="form-label" for="code">{{ __('SMS code') }}</label>
            <input type="text" class="form-control text-center fs-4 fw-semibold @error('code') is-invalid @enderror" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" placeholder="000000" style="letter-spacing: .5em;" required autofocus>
            @error('code')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <p class="form-text mb-0">{{ __('The code expires in :minutes minutes.', ['minutes' => $minutes]) }}</p>
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary waves-effect waves-light w-100">{{ __('Verify and create account') }}</button>
        </div>
    </form>
    <form method="POST" action="{{ route('register.verify.resend') }}" class="text-center mb-2">
        @csrf
        <span class="form-text">{{ __('No SMS yet?') }}</span>
        <button type="submit" class="btn btn-link p-0 align-baseline">{{ __('Send a new code') }}</button>
    </form>
    <p class="mb-0 text-center"><a href="{{ route('register') }}">{{ __('Use a different number') }}</a></p>
@endsection
