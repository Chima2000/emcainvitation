@extends('layouts.auth')

@section('title', __('Sign in').' | '.config('app.name'))

@section('content')
    @php($startOnReset = $errors->has('reset_phone'))
    <div class="jumuika-flip" data-auth-flip>
        <div @class(['jumuika-flip-rotor', 'is-reset' => $startOnReset]) data-auth-rotor>
            <section class="jumuika-flip-face jumuika-flip-face--front" data-auth-face="login" aria-hidden="{{ $startOnReset ? 'true' : 'false' }}" @if ($startOnReset) inert @endif>
                <a href="{{ route('home') }}" aria-label="Jumuika Pass">
                    <span class="jumuika-auth-logo-frame">
                        <img class="jumuika-auth-logo" src="{{ asset('logo/Jumuika-logo.png') }}" alt="Jumuika Pass">
                    </span>
                </a>
                <h1>{{ __('Sign in') }}</h1>
                <p class="jumuika-auth-lead">{{ __('Sign in with the phone number that starts with 0.') }}</p>
                @if (session('status'))
                    <div class="alert alert-success py-2 small" role="status">{{ session('status') }}</div>
                @endif
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label" for="phone">{{ __('Phone number') }}</label>
                        <x-phone-input id="phone" name="phone" value="{{ old('phone') }}" placeholder="0692586046" inputmode="tel" autocomplete="username" required autofocus />
                        <p class="form-text mb-0">{{ __('Enter the number you use to sign in. It starts with 0, for example 0692586046.') }}</p>
                        @error('phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <div class="jumuika-auth-label-row">
                            <label class="form-label" for="password">{{ __('Password') }}</label>
                            <button type="button" class="jumuika-flip-toggle" data-auth-flip-to="reset">{{ __('Reset password') }}</button>
                        </div>
                        <x-password-input id="password" name="password" placeholder="********" autocomplete="current-password" required />
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
                            <label class="form-check-label" for="remember">{{ __('Remember this browser') }}</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary waves-effect waves-light w-100">{{ __('Sign in') }}</button>
                    </div>
                    <p class="mb-0 text-center">{{ __('New here?') }} <a href="{{ route('register') }}">{{ __('Create an account') }}</a></p>
                </form>
            </section>
            <section class="jumuika-flip-face jumuika-flip-face--back" data-auth-face="reset" aria-hidden="{{ $startOnReset ? 'false' : 'true' }}" @unless ($startOnReset) inert @endunless>
                <a href="{{ route('home') }}" aria-label="Jumuika Pass" tabindex="-1">
                    <span class="jumuika-auth-logo-frame">
                        <img class="jumuika-auth-logo" src="{{ asset('logo/Jumuika-logo.png') }}" alt="">
                    </span>
                </a>
                <h1>{{ __('Reset password') }}</h1>
                <p class="jumuika-auth-lead">{{ __('We will send a 6-digit code by SMS to the number you registered with.') }}</p>
                <form method="POST" action="{{ route('password.sms') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label" for="reset-phone">{{ __('Phone number') }}</label>
                        <x-phone-input id="reset-phone" name="reset_phone" value="{{ old('reset_phone') }}" placeholder="0692586046" inputmode="tel" autocomplete="username" required />
                        <p class="form-text mb-0">{{ __('Enter the number you use to sign in. It starts with 0, for example 0692586046.') }}</p>
                        @error('reset_phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary waves-effect waves-light w-100">{{ __('Send the code') }}</button>
                    </div>
                    <p class="mb-0 text-center">
                        <button type="button" class="jumuika-flip-toggle" data-auth-flip-to="login">{{ __('Back to sign in') }}</button>
                    </p>
                </form>
            </section>
        </div>
    </div>
    <script>
        (function () {
            const scene = document.querySelector('[data-auth-flip]');
            const rotor = document.querySelector('[data-auth-rotor]');
            const stage = document.querySelector('.jumuika-auth');

            if (!scene || !rotor || !stage) {
                return;
            }

            const front = scene.querySelector('[data-auth-face="login"]');
            const back = scene.querySelector('[data-auth-face="reset"]');
            let playing = false;
            let flipTimer = 0;

            function showFace(toReset) {
                front.toggleAttribute('inert', toReset);
                back.toggleAttribute('inert', !toReset);
                front.setAttribute('aria-hidden', toReset ? 'true' : 'false');
                back.setAttribute('aria-hidden', toReset ? 'false' : 'true');
            }

            function focusFace(toReset) {
                const face = toReset ? back : front;
                const field = face.querySelector('input');

                if (field) {
                    field.focus();
                }
            }

            function finishFlip(toReset) {
                if (!playing) {
                    return;
                }

                window.clearTimeout(flipTimer);
                playing = false;
                rotor.classList.remove('is-playing-reset', 'is-playing-login');
                rotor.classList.toggle('is-reset', toReset);
                stage.classList.remove('is-card-jumping');
                showFace(toReset);
                focusFace(toReset);
            }

            function flip(toReset) {
                if (playing || rotor.classList.contains('is-reset') === toReset) {
                    return;
                }

                const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (reduceMotion) {
                    playing = true;
                    finishFlip(toReset);

                    return;
                }

                playing = true;
                front.setAttribute('inert', '');
                back.setAttribute('inert', '');
                stage.classList.add('is-card-jumping');
                rotor.classList.add(toReset ? 'is-playing-reset' : 'is-playing-login');
                flipTimer = window.setTimeout(function () {
                    finishFlip(toReset);
                }, 1500);
            }

            rotor.addEventListener('animationend', function (event) {
                if (event.target !== rotor || event.animationName !== 'jumuika-flip-away') {
                    return;
                }

                finishFlip(rotor.classList.contains('is-playing-reset'));
            });

            scene.addEventListener('click', function (event) {
                const trigger = event.target.closest('[data-auth-flip-to]');

                if (!trigger) {
                    return;
                }

                flip(trigger.getAttribute('data-auth-flip-to') === 'reset');
            });
        }());
    </script>
@endsection
