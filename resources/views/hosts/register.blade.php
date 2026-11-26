@extends('layouts.dashboard')

@section('title', __('Become a host'))
@section('heading', __('Become a host'))
@section('subheading', __('Use the same steps as registration. Payment comes after you are a host.'))

@section('content')
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    @if ($user->hasActiveSubscription())
                        <div class="alert alert-info" role="status">
                            {{ __('Your trial continues until :date. Registering does not charge you.', ['date' => $user->subscription_ends_at->timezone(config('app.timezone'))->translatedFormat('j F Y')]) }}
                        </div>
                    @else
                        <div class="alert alert-warning" role="status">
                            {{ __('Your trial has ended. Register as a host, then choose a subscription.') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('become-host.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">{{ __('First name') }}</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" autocomplete="given-name" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">{{ __('Last name') }}</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" autocomplete="family-name" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">{{ __('Phone') }}</label>
                                <x-phone-input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" inputmode="tel" autocomplete="tel" required />
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">{{ __('Email') }}</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">{{ __('Password') }}</label>
                                <x-password-input id="password" name="password" autocomplete="new-password" required />
                                <p class="form-text mb-0">{{ __('Use more than 8 characters, with an uppercase letter, a number, and a symbol.') }}</p>
                                @foreach ($errors->get('password') as $message)
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @endforeach
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">{{ __('Repeat password') }}</label>
                                <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" required />
                            </div>
                        </div>
                        <p class="form-text mt-3 mb-3">{{ __('Creating an account means you accept the') }} <a href="{{ route('terms') }}">{{ __('Terms and conditions') }}</a> {{ __('and') }} <a href="{{ route('privacy') }}">{{ __('Privacy policy') }}</a>.</p>
                        <button type="submit" class="btn btn-primary">{{ __('Register as a host') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
