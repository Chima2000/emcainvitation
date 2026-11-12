@extends('layouts.auth')

@section('title', __('Choose a new password').' | '.config('app.name'))
@section('heading', __('Choose a new password'))
@section('subheading', __('Enter the code we sent by SMS to :phone, then your new password.', ['phone' => $maskedPhone]))

@section('content')
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <div class="mb-4">
            <label class="form-label" for="code">{{ __('SMS code') }}</label>
            <input type="text" class="form-control text-center fs-4 fw-semibold @error('code') is-invalid @enderror" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}" maxlength="7" placeholder="000000" style="letter-spacing: .5em;" required autofocus>
            @error('code')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <p class="form-text mb-0">{{ __('The code expires in :minutes minutes.', ['minutes' => $minutes]) }}</p>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">{{ __('New password') }}</label>
            <x-password-input id="password" name="password" placeholder="********" autocomplete="new-password" required />
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-4">
            <label class="form-label" for="password_confirmation">{{ __('Repeat password') }}</label>
            <x-password-input id="password_confirmation" name="password_confirmation" placeholder="********" autocomplete="new-password" required />
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary waves-effect waves-light w-100">{{ __('Save new password') }}</button>
        </div>
    </form>
    <form method="POST" action="{{ route('password.sms') }}" class="text-center mb-2">
        @csrf
        <input type="hidden" name="reset_phone" value="{{ $localPhone }}">
        <span class="form-text">{{ __('No SMS yet?') }}</span>
        <button type="submit" class="btn btn-link p-0 align-baseline">{{ __('Send a new code') }}</button>
    </form>
    <p class="mb-0 text-center"><a href="{{ route('login') }}">{{ __('Back to sign in') }}</a></p>
@endsection
