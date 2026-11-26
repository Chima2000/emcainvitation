<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#940000">
        <title>@yield('title', config('app.name'))</title>
        @include('layouts.partials.admin-head')
        <link rel="stylesheet" href="{{ asset('admin/assets/css/emca-ajax.css') }}?v={{ filemtime(public_path('admin/assets/css/emca-ajax.css')) }}">
        <style>
            html,
            body {
                height: 100%;
                overflow: hidden;
                background: #f5f5f5;
                color: #1c2434;
            }

            .jumuika-auth {
                position: relative;
                height: 100vh;
                height: 100dvh;
                overflow: hidden;
                display: grid;
                place-items: center;
                padding: 16px;
                background: #1f1a1e;
            }

            .jumuika-auth-wall {
                position: absolute;
                inset: -28px;
                z-index: 0;
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                grid-auto-rows: 210px;
                align-content: start;
                gap: 0;
                pointer-events: none;
                animation: jumuika-wall-drift 60s linear infinite alternate;
            }

            .jumuika-auth-wall img {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center 30%;
                border-radius: 0;
            }

            .jumuika-auth-shade {
                position: absolute;
                inset: 0;
                z-index: 1;
                background:
                    radial-gradient(circle at center, rgba(12, 10, 14, 0.32) 0, rgba(12, 10, 14, 0.42) 55%, rgba(12, 10, 14, 0.58) 100%);
                pointer-events: none;
            }

            @keyframes jumuika-wall-drift {
                from {
                    transform: translateY(0);
                }

                to {
                    transform: translateY(-120px);
                }
            }

            @media (max-width: 991.98px) {
                .jumuika-auth-wall {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    grid-auto-rows: 170px;
                }
            }

            @media (max-width: 575.98px) {
                .jumuika-auth-wall {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    grid-auto-rows: 150px;
                }
            }

            .jumuika-auth-back,
            .jumuika-auth-tools {
                position: absolute;
                top: 16px;
                z-index: 3;
            }

            .jumuika-auth-back {
                left: 16px;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 14px;
                border-radius: 999px;
                background: rgba(0, 0, 0, 0.5);
                border: 1px solid rgba(255, 255, 255, 0.35);
                backdrop-filter: blur(6px);
                -webkit-backdrop-filter: blur(6px);
                color: #fff;
                font-size: 0.92rem;
                font-weight: 650;
                text-decoration: none;
            }

            .jumuika-auth-back:hover {
                color: #fff;
                background: rgba(0, 0, 0, 0.58);
            }

            .jumuika-auth-tools {
                right: 16px;
            }

            .jumuika-auth-tools .btn {
                background: rgba(255, 255, 255, 0.94);
                border-color: transparent;
            }

            .jumuika-auth-card {
                position: relative;
                z-index: 2;
                width: min(100%, 420px);
                margin: 0;
                padding: 16px 22px 14px;
                overflow: visible;
                background: #fff;
                color: #1c2434;
                border: 0;
                border-top: 4px solid #940000;
                border-radius: 16px;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
            }

            .jumuika-auth-card-wide {
                width: min(100%, 680px);
                max-height: calc(100dvh - 88px);
                margin-top: 40px;
                overflow-y: auto;
                overscroll-behavior: contain;
                scrollbar-width: thin;
                scrollbar-color: rgba(28, 36, 52, 0.18) transparent;
            }

            .jumuika-auth-logo-frame {
                display: block;
                width: 168px;
                height: auto;
                margin: 4px auto 8px;
                overflow: visible;
            }

            .jumuika-auth-logo {
                display: block;
                width: 168px;
                max-width: 100%;
                height: auto;
                margin: 0;
            }

            .jumuika-auth-mark {
                margin: 0 0 14px;
                text-align: center;
                color: #940000;
                font-size: 0.78rem;
                font-weight: 650;
                letter-spacing: 0.01em;
            }

            .jumuika-auth-card h1 {
                margin: 0 0 2px;
                text-align: center;
                font-size: 1.35rem;
                font-weight: 700;
                letter-spacing: -0.03em;
                color: #1c2434;
            }

            .jumuika-auth-card .jumuika-auth-lead {
                margin: 0 0 12px;
                text-align: center;
                font-size: 0.9rem;
                color: #667085;
            }

            .jumuika-auth-card .form-label {
                margin-bottom: 0.2rem;
                font-size: 0.78rem;
                font-weight: 600;
                color: #344054;
            }

            .jumuika-auth-card .mb-4 {
                margin-bottom: 0.85rem !important;
            }

            .jumuika-auth-card .mb-3 {
                margin-bottom: 0.7rem !important;
            }

            .jumuika-auth-card .form-control {
                min-height: 40px;
                padding-top: 0.35rem;
                padding-bottom: 0.35rem;
                border-radius: 10px;
                border: 1px solid #e4e7ee;
                background: #fff;
                color: #1c2434;
                box-shadow: none;
            }

            .jumuika-auth-card .form-control:focus {
                border-color: #940000;
                box-shadow: 0 0 0 3px rgba(148, 0, 0, 0.12);
            }

            .jumuika-phone {
                position: relative;
            }

            .jumuika-phone-flag {
                position: absolute;
                top: 20px;
                left: 10px;
                z-index: 2;
                display: flex;
                transform: translateY(-50%);
                pointer-events: none;
            }

            .jumuika-phone-flag .emca-locale-flag {
                width: 22px;
                height: 22px;
                border-radius: 50%;
                box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.12);
                overflow: hidden;
            }

            .jumuika-auth-card .jumuika-phone-input {
                padding-left: 40px;
            }

            .jumuika-password {
                position: relative;
            }

            .jumuika-password-toggle {
                position: absolute;
                top: 20px;
                right: 8px;
                z-index: 2;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                padding: 0;
                border: 0;
                border-radius: 8px;
                transform: translateY(-50%);
                background: transparent;
                color: #667085;
            }

            .jumuika-password-toggle:hover,
            .jumuika-password-toggle:focus {
                color: #940000;
            }

            .jumuika-password-toggle .fi-rr-eye-crossed {
                display: none;
            }

            .jumuika-password.is-visible .jumuika-password-toggle .fi-rr-eye {
                display: none;
            }

            .jumuika-password.is-visible .jumuika-password-toggle .fi-rr-eye-crossed {
                display: inline-block;
            }

            .jumuika-auth-card .jumuika-password-input {
                padding-right: 42px;
            }

            .jumuika-auth-card .jumuika-password-input.is-invalid {
                background-image: none;
                padding-right: 42px;
            }

            .jumuika-auth-card .btn-primary {
                min-height: 42px;
                border-radius: 10px;
                background: #940000;
                border-color: #940000;
                font-weight: 650;
            }

            .jumuika-auth-card .btn-primary:hover,
            .jumuika-auth-card .btn-primary:focus {
                background: #7a0000;
                border-color: #7a0000;
            }

            .jumuika-auth-card a {
                color: #940000;
                font-weight: 650;
                text-decoration: none;
            }

            .jumuika-auth-card a:hover {
                color: #7a0000;
            }

            .jumuika-auth-card a.btn-primary,
            .jumuika-auth-card a.btn-primary:hover,
            .jumuika-auth-card a.btn-primary:focus {
                color: #fff;
            }

            .jumuika-auth-card--flip {
                padding: 0;
                overflow: visible;
                background: transparent;
                border-top: 0;
                border-radius: 0;
                box-shadow: none;
                max-height: none;
            }

            .jumuika-flip {
                perspective: 1600px;
                perspective-origin: 50% 42%;
            }

            .jumuika-flip-rotor {
                --flip-jump: -148px;
                --flip-side: 72px;
                display: grid;
                transform-style: preserve-3d;
            }

            .jumuika-flip-face {
                grid-area: 1 / 1;
                padding: 16px 22px 14px;
                background: #fff;
                border-top: 4px solid #940000;
                border-radius: 24px;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
                backface-visibility: hidden;
                -webkit-backface-visibility: hidden;
            }

            .jumuika-flip-face--front {
                transform: rotateY(0deg);
            }

            .jumuika-flip-face--back {
                transform: rotateY(180deg);
                pointer-events: none;
            }

            .jumuika-flip-rotor.is-reset {
                transform: rotateY(-180deg);
            }

            .jumuika-flip-rotor.is-reset .jumuika-flip-face--back {
                pointer-events: auto;
            }

            .jumuika-flip-rotor.is-reset .jumuika-flip-face--front {
                pointer-events: none;
            }

            .jumuika-flip-rotor.is-playing-reset {
                animation: jumuika-flip-away 1.28s linear both;
            }

            .jumuika-flip-rotor.is-playing-login {
                animation: jumuika-flip-away 1.28s linear reverse both;
            }

            .jumuika-flip-rotor.is-playing-reset .jumuika-flip-face,
            .jumuika-flip-rotor.is-playing-login .jumuika-flip-face {
                animation: jumuika-flip-lift 1.28s linear both;
                pointer-events: none;
            }

            .jumuika-auth.is-card-jumping {
                overflow: visible;
            }

            .jumuika-auth.is-card-jumping .jumuika-auth-card {
                z-index: 5;
            }

            .jumuika-auth-label-row {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 12px;
            }

            .jumuika-flip-toggle {
                padding: 0;
                border: 0;
                background: transparent;
                color: #940000;
                font: inherit;
                font-size: 0.82rem;
                font-weight: 650;
                line-height: 1.2;
                cursor: pointer;
            }

            .jumuika-flip-toggle:hover,
            .jumuika-flip-toggle:focus-visible {
                color: #7a0000;
            }

            @keyframes jumuika-flip-away {
                0% {
                    transform: translate3d(0, 0, 0) rotateX(0deg) rotateY(0deg) rotateZ(0deg) scale(1);
                }

                9% {
                    transform: translate3d(0, 26px, 0) rotateX(8deg) rotateY(0deg) rotateZ(0deg) scale(0.94);
                }

                32% {
                    transform: translate3d(calc(var(--flip-side) * -1), calc(var(--flip-jump) * 0.82), 48px) rotateX(-10deg) rotateY(-48deg) rotateZ(-7deg) scale(1.08);
                }

                50% {
                    transform: translate3d(var(--flip-side), var(--flip-jump), 56px) rotateX(8deg) rotateY(-128deg) rotateZ(6deg) scale(1.1);
                }

                72% {
                    transform: translate3d(calc(var(--flip-side) * -0.35), -24px, 12px) rotateX(-4deg) rotateY(-174deg) rotateZ(-3deg) scale(1.02);
                }

                84% {
                    transform: translate3d(6px, 24px, 0) rotateX(5deg) rotateY(-188deg) rotateZ(2deg) scale(0.95);
                }

                93% {
                    transform: translate3d(-4px, -18px, 8px) rotateX(-2deg) rotateY(-172deg) rotateZ(-1deg) scale(1.05);
                }

                100% {
                    transform: translate3d(0, 0, 0) rotateX(0deg) rotateY(-180deg) rotateZ(0deg) scale(1);
                }
            }

            @keyframes jumuika-flip-lift {
                0%,
                100% {
                    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
                }

                9% {
                    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.22);
                }

                50% {
                    box-shadow: 0 18px 30px rgba(0, 0, 0, 0.12);
                }

                84% {
                    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.24);
                }

                93% {
                    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
                }
            }

            @media (max-height: 760px) {
                .jumuika-flip-rotor {
                    --flip-jump: -78px;
                    --flip-side: 36px;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .jumuika-auth-wall,
                .jumuika-flip-rotor.is-playing-reset,
                .jumuika-flip-rotor.is-playing-login,
                .jumuika-flip-rotor.is-playing-reset .jumuika-flip-face,
                .jumuika-flip-rotor.is-playing-login .jumuika-flip-face {
                    animation: none;
                }
            }

            @media (max-width: 767.98px) {
                html,
                body {
                    height: auto;
                    min-height: 100%;
                    overflow: auto;
                }

                .jumuika-auth {
                    display: grid;
                    grid-template-columns: auto minmax(0, 1fr);
                    align-items: start;
                    align-content: start;
                    height: auto;
                    min-height: 100dvh;
                    overflow: visible;
                    gap: 8px 8px;
                    padding: calc(8px + env(safe-area-inset-top)) 10px calc(12px + env(safe-area-inset-bottom));
                    place-items: stretch;
                }

                .jumuika-auth-wall,
                .jumuika-auth-shade {
                    grid-column: 1 / -1;
                    grid-row: 1 / -1;
                }

                .jumuika-auth-back,
                .jumuika-auth-tools {
                    position: static;
                    top: auto;
                }

                .jumuika-auth-back {
                    grid-column: 1;
                    grid-row: 1;
                    justify-self: start;
                    gap: 0;
                    width: 32px;
                    height: 32px;
                    padding: 0;
                    justify-content: center;
                    font-size: 14px;
                }

                .jumuika-auth-back span {
                    display: none;
                }

                .jumuika-auth-tools {
                    grid-column: 2;
                    grid-row: 1;
                    justify-self: end;
                }

                .jumuika-auth-card,
                .jumuika-auth-card-wide,
                .jumuika-flip-face {
                    grid-column: 1 / -1;
                    width: 100%;
                    max-height: none;
                    margin-top: 0;
                    overflow: visible;
                    padding: 8px;
                    border-radius: 0.4rem;
                    font-size: 0.6875rem;
                    line-height: 1.35;
                }

                .jumuika-auth-card--flip {
                    padding: 0;
                    background: transparent;
                    box-shadow: none;
                }

                .jumuika-flip-rotor {
                    --flip-jump: -36px;
                    --flip-side: 16px;
                }

                .jumuika-auth-logo-frame {
                    width: 120px;
                    height: auto;
                    margin: 0 auto 6px;
                }

                .jumuika-auth-logo {
                    width: 120px;
                    height: auto;
                    margin: 0;
                }

                .jumuika-auth-mark {
                    margin-bottom: 8px;
                    font-size: 0.68rem;
                }

                .jumuika-auth-card h1 {
                    font-size: 1.05rem;
                    margin-bottom: 2px;
                }

                .jumuika-auth-card .jumuika-auth-lead,
                .jumuika-auth-card .form-text,
                .jumuika-auth-card .form-check-label,
                .jumuika-flip-toggle {
                    font-size: 0.6875rem;
                }

                .jumuika-auth-card .form-label {
                    margin-bottom: 0.15rem;
                    font-size: 0.6875rem;
                }

                .jumuika-auth-card .mb-4 {
                    margin-bottom: 0.45rem !important;
                }

                .jumuika-auth-card .mb-3 {
                    margin-bottom: 0.4rem !important;
                }

                .jumuika-auth-card .row > .col-6 {
                    flex: 0 0 100%;
                    width: 100%;
                    max-width: 100%;
                }

                .jumuika-auth-card .form-control,
                .jumuika-auth-card .form-select {
                    min-height: 0;
                    padding: 0.22rem 0.45rem;
                    font-size: 0.75rem;
                    line-height: 1.35;
                    border-radius: 6px;
                }

                .jumuika-phone-flag,
                .jumuika-password-toggle {
                    top: 50%;
                }

                .jumuika-phone-flag .emca-locale-flag {
                    width: 16px;
                    height: 16px;
                }

                .jumuika-auth-card .jumuika-phone-input {
                    padding-left: 30px;
                }

                .jumuika-auth-card .btn-primary {
                    min-height: 26px;
                    padding: 0.24rem 0.5rem;
                    font-size: 0.7rem;
                    border-radius: 6px;
                }
            }

            @media (max-width: 767.98px) {
                .jumuika-auth-wall,
                .jumuika-auth-shade {
                    position: fixed;
                    inset: 0;
                }

                .jumuika-auth-wall {
                    overflow: hidden;
                }

                .jumuika-auth {
                    gap: 12px 10px;
                    padding-right: 16px;
                    padding-left: 16px;
                }

                .jumuika-auth-shade {
                    background: rgba(12, 10, 14, 0.62);
                }

                .jumuika-auth-back {
                    width: 44px;
                    height: 44px;
                    font-size: 18px;
                }

                .jumuika-auth-tools .btn {
                    min-height: 44px;
                    padding: 0.55rem 0.85rem;
                    font-size: 0.875rem;
                }

                .jumuika-auth-card:not(.jumuika-auth-card--flip),
                .jumuika-auth-card-wide,
                .jumuika-flip-face {
                    padding: 18px;
                    border-radius: 20px;
                    font-size: 1rem;
                    line-height: 1.5;
                }

                .jumuika-auth-card--flip {
                    padding: 0;
                }

                .jumuika-auth-logo-frame,
                .jumuika-auth-logo {
                    width: 148px;
                }

                .jumuika-auth-mark {
                    margin-bottom: 12px;
                    font-size: 0.8125rem;
                }

                .jumuika-auth-card h1 {
                    margin-bottom: 6px;
                    font-size: 1.5rem;
                    line-height: 1.25;
                }

                .jumuika-auth-card .jumuika-auth-lead,
                .jumuika-auth-card .form-text,
                .jumuika-auth-card .form-check-label,
                .jumuika-flip-toggle {
                    font-size: 0.875rem;
                    line-height: 1.45;
                }

                .jumuika-auth-card .form-label {
                    margin-bottom: 0.35rem;
                    font-size: 0.875rem;
                }

                .jumuika-auth-card .mb-4 {
                    margin-bottom: 0.8rem !important;
                }

                .jumuika-auth-card .mb-3 {
                    margin-bottom: 0.7rem !important;
                }

                .jumuika-auth-card .form-control,
                .jumuika-auth-card .form-select {
                    min-height: 48px;
                    padding: 0.65rem 0.8rem;
                    border-radius: 12px;
                    font-size: 1rem;
                    line-height: 1.4;
                }

                .jumuika-auth-card .jumuika-phone-input {
                    padding-left: 42px;
                }

                .jumuika-phone-flag,
                .jumuika-password-toggle {
                    width: 40px;
                    height: 40px;
                }

                .jumuika-phone-flag .emca-locale-flag {
                    width: 20px;
                    height: 20px;
                }

                .jumuika-auth-card .btn-primary {
                    min-height: 48px;
                    padding: 0.65rem 1rem;
                    border-radius: 12px;
                    font-size: 1rem;
                }

                .jumuika-flip-toggle {
                    min-height: 44px;
                    padding: 8px;
                }
            }
        </style>
    </head>
    <body data-emca-layout="auth-{{ request()->routeIs('register', 'register.verify') ? 'register' : 'login' }}" data-emca-swap="all">
        @php
            $isRegister = request()->routeIs('register', 'register.verify');
            $isFlip = request()->routeIs('login');
            $wallPhotos = collect($isRegister
                ? [
                    'images/occasions/gathering.jpg',
                    'images/auth/register-2.jpg',
                    'images/home/occasion-kitchen-party.jpg',
                    'images/occasions/birthday.jpg',
                    'images/auth/register-3.jpg',
                    'images/home/choose-us.jpg',
                    'images/occasions/sendoff.jpg',
                    'images/auth/register-13.jpg',
                    'images/home/occasion-meeting.jpg',
                    'images/home/faq.jpg',
                    'images/auth/register-6.jpg',
                    'images/home/occasion-birthday.jpg',
                    'images/features/tasks-vendors.jpg',
                    'images/auth/register-14.jpg',
                    'images/auth/register-16.jpg',
                ]
                : [
                    'images/home/occasion-wedding.jpg',
                    'images/auth/login-2.jpg',
                    'images/occasions/celebration.jpg',
                    'images/home/hero.jpg',
                    'images/auth/login-15.jpg',
                    'images/home/upcoming.jpg',
                    'images/features/intro.jpg',
                    'images/auth/login-6.jpg',
                    'images/occasions/wedding.jpg',
                    'images/home/occasion-celebration.jpg',
                    'images/auth/login-9.jpg',
                    'images/features/pledges.jpg',
                    'images/home/occasion-sendoff.jpg',
                ]);
            $wallPhotos = $wallPhotos->concat($wallPhotos)->concat($wallPhotos)->take(35);
        @endphp
        <div class="jumuika-auth">
            <div @class(['jumuika-auth-wall', 'jumuika-wall-register' => $isRegister, 'jumuika-wall-login' => ! $isRegister]) aria-hidden="true">
                @foreach ($wallPhotos as $wallPhoto)
                    <img src="{{ asset($wallPhoto) }}" alt="" loading="{{ $loop->index < 10 ? 'eager' : 'lazy' }}" decoding="async">
                @endforeach
            </div>
            <div class="jumuika-auth-shade" aria-hidden="true"></div>
            <a class="jumuika-auth-back" href="{{ route('home') }}">
                <i class="fi fi-rr-arrow-small-left"></i>
                <span>{{ __('Back to website') }}</span>
            </a>
            <div class="jumuika-auth-tools">
                <x-locale-switcher tone="dashboard" />
            </div>
            <main @class(['jumuika-auth-card', 'jumuika-auth-card-wide' => request()->routeIs('register'), 'jumuika-auth-card--flip' => $isFlip]) data-emca-region="page">
                @if ($isFlip)
                    @yield('content')
                @else
                    <a href="{{ route('home') }}" aria-label="Jumuika Pass">
                        <span class="jumuika-auth-logo-frame">
                            <img class="jumuika-auth-logo" src="{{ asset('logo/jumuika-pass.png') }}" alt="Jumuika Pass" width="757" height="389">
                        </span>
                    </a>
                    <p class="jumuika-auth-mark">{{ __('Invite better. Manage better. Spend less.') }}</p>
                    <h1>@yield('heading')</h1>
                    <p class="jumuika-auth-lead">@yield('subheading')</p>
                    @if (session('status'))
                        <div class="alert alert-success py-2 small" role="status" data-emca-dismiss>{{ session('status') }}</div>
                    @endif
                    @yield('content')
                @endif
            </main>
        </div>
        @include('layouts.partials.admin-scripts')
        @include('layouts.partials.ajax-script')
        <div data-emca-region="scripts">
            @stack('scripts')
        </div>
        <script>
            function lockLightTheme() {
                document.documentElement.setAttribute('data-bs-theme', 'light');
            }

            lockLightTheme();
            document.addEventListener('DOMContentLoaded', function () {
                lockLightTheme();
                window.setTimeout(lockLightTheme, 0);
            });
        </script>
    </body>
</html>
