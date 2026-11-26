@extends('layouts.auth')

@section('title', __('Your trial').' | '.config('app.name'))
@section('heading', __('Your trial is ready'))
@section('subheading', __('This trial runs for :days days, until :date.', [
    'days' => $demo->trial_days,
    'date' => $demo->trial_ends_at->timezone(config('app.timezone'))->translatedFormat('j F Y, g:i A'),
]))

@section('content')
    @if ($demo->must_set_password)
        <form method="POST" action="{{ url()->full() }}">
            @csrf
            <div class="mb-4">
                <label class="form-label" for="password">{{ __('Choose a password') }}</label>
                <x-password-input id="password" name="password" placeholder="********" autocomplete="new-password" required />
                <p class="form-text mb-0">{{ __('Use more than 8 characters, with an uppercase letter, a number, and a symbol.') }}</p>
                @foreach ($errors->get('password') as $message)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @endforeach
            </div>
            <div class="mb-4">
                <label class="form-label" for="password_confirmation">{{ __('Repeat password') }}</label>
                <x-password-input id="password_confirmation" name="password_confirmation" placeholder="********" autocomplete="new-password" required />
            </div>
            <button type="submit" class="btn btn-primary waves-effect waves-light w-100">{{ __('Start your trial') }}</button>
        </form>
    @else
        <p class="mb-4">{{ __('Your trial is already on this account. Sign in with your phone number.') }}</p>
        <a href="{{ route('login') }}" class="btn btn-primary waves-effect waves-light w-100">{{ __('Sign in') }}</a>
    @endif
@endsection
