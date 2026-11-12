@extends('layouts.auth')

@section('title', __('Create an account').' | '.config('app.name'))
@section('heading', __('Create an account'))
@section('subheading', __('Sign up to start a guest list.'))

@section('content')
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="text-center mb-3">
            <p class="form-text mb-0">{{ __('Create your account first. You can add your location after registration.') }}</p>
        </div>
        <div class="row g-2">
            <div class="col-6 mb-4">
                <label class="form-label" for="first_name">{{ __('First name') }}</label>
                <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="{{ __('First name') }}" autocomplete="given-name" required autofocus>
                @error('first_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-6 mb-4">
                <label class="form-label" for="last_name">{{ __('Last name') }}</label>
                <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="{{ __('Last name') }}" autocomplete="family-name" required>
                @error('last_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="row g-2">
            <div class="col-6 mb-4">
                <label class="form-label" for="phone">{{ __('Phone') }}</label>
                <x-phone-input id="phone" name="phone" value="{{ old('phone', '+255') }}" placeholder="+255692586046" inputmode="tel" autocomplete="tel" required />
                <p class="form-text mb-0">{{ __('Enter 10 digits starting with 0 or 12 digits starting with 255, for example 0692586046 or 255692586046.') }}</p>
                @error('phone')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-6 mb-4">
                <label class="form-label" for="email">{{ __('Email') }}</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="info@example.com" autocomplete="email" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">{{ __('Password') }}</label>
            <x-password-input id="password" name="password" placeholder="********" autocomplete="new-password" required />
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div class="mb-4">
            <label class="form-label" for="password_confirmation">{{ __('Repeat password') }}</label>
            <x-password-input id="password_confirmation" name="password_confirmation" placeholder="********" autocomplete="new-password" required />
        </div>
        <p class="form-text text-center mb-2">{{ __('Creating an account means you accept the') }} <a href="{{ route('terms') }}">{{ __('Terms and conditions') }}</a> {{ __('and') }} <a href="{{ route('privacy') }}">{{ __('Privacy policy') }}</a>.</p>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary waves-effect waves-light w-100">{{ __('Create account') }}</button>
        </div>
        <p class="mb-0 text-center">{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
    </form>
@endsection
