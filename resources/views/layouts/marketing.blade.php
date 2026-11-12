@php
    $insectra = asset('invitationtemplate/insectra-html');
    $themeStyles = 'invitationtemplate/insectra-html/css/style.css';
    $brandStyles = 'invitationtemplate/insectra-html/css/emca-brand.css';
    $logo = asset('logo/jumuika-pass.png');
    $supportPhoneLink = 'tel:'.preg_replace('/\s+/', '', config('emca.support_phone'));
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="theme-color" content="#940000">
        <meta name="description" content="{{ __('Emca designs digital invitation cards, tracks replies, and checks guests in with a code that works once.') }}">
        <title>@yield('title', config('app.name'))</title>
        @include('layouts.partials.favicon')
        <link rel="stylesheet" href="{{ $insectra }}/css/bootstrap.min.css">
        <link rel="stylesheet" href="{{ asset($themeStyles) }}?v={{ filemtime(public_path($themeStyles)) }}">
        <link rel="stylesheet" href="{{ asset($brandStyles) }}?v={{ filemtime(public_path($brandStyles)) }}">
        <link rel="stylesheet" href="{{ asset('admin/assets/css/emca-ajax.css') }}?v={{ filemtime(public_path('admin/assets/css/emca-ajax.css')) }}">
        @stack('head')
    </head>
    <body data-emca-layout="marketing">
        <div class="page-wrapper">
            <div class="preloader"></div>

            <div class="back-to-top-wrapper">
                <button id="back_to_top" type="button" class="back-to-top-btn" aria-label="{{ __('Back to top') }}">
                    <svg width="12" height="7" viewBox="0 0 12 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 6L6 1L1 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>

            <header class="main-header header-style-one">
                <div class="outer-box">
                    <div class="header-lower">
                        <div class="inner-container">
                            <div class="main-box">
                                <div class="logo-box">
                                    <div class="logo">
                                        <a href="{{ route('home') }}"><img class="emca-logo-light" src="{{ $logo }}" alt="Jumuika Pass"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="nav-outer">
                                <nav class="nav main-menu">
                                    <ul class="navigation">
                                        @include('layouts.partials.public-menu')
                                    </ul>
                                </nav>
                            </div>
                            <div class="right-box">
                                <x-locale-switcher tone="marketing" class="emca-header-locale" />
                                @guest
                                    <a href="{{ route('register') }}" class="theme-btn btn-style-two">{{ __('Register') }}</a>
                                @endguest
                                @auth
                                    <a class="ui-btn emca-account-btn" href="{{ route('dashboard') }}" aria-label="{{ __('Dashboard') }}"><i class="fa-regular fa-user"></i></a>
                                @else
                                    <a class="ui-btn emca-account-btn" href="{{ route('login') }}" aria-label="{{ __('Sign in') }}"><i class="fa-regular fa-right-to-bracket"></i></a>
                                @endauth
                                <div class="mobile-nav-toggler">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mobile-menu">
                    <div class="menu-backdrop"></div>
                    <nav class="menu-box">
                        <div class="upper-box">
                            <div class="nav-logo">
                                <a href="{{ route('home') }}"><img class="emca-logo-light" src="{{ $logo }}" alt="Jumuika Pass"></a>
                            </div>
                            <div class="close-btn"><i class="icon fa fa-times"></i></div>
                        </div>
                        <ul class="navigation clearfix"></ul>
                        <ul class="contact-list-one">
                            <li>
                                <div class="contact-info-box">
                                    <i class="icon lnr-icon-phone-handset"></i>
                                    <span class="title">{{ __('Call us') }}</span>
                                    <a href="{{ $supportPhoneLink }}">{{ config('emca.support_phone') }}</a>
                                </div>
                            </li>
                            <li>
                                <div class="contact-info-box">
                                    <span class="icon lnr-icon-envelope1"></span>
                                    <span class="title">{{ __('Send an email') }}</span>
                                    <a href="mailto:{{ config('emca.support_email') }}">{{ config('emca.support_email') }}</a>
                                </div>
                            </li>
                            <li>
                                <div class="contact-info-box">
                                    <span class="icon lnr-icon-map-marker"></span>
                                    <span class="title">{{ __('Visit us') }}</span>
                                    {{ config('emca.support_address') }}
                                </div>
                            </li>
                        </ul>
                        <div class="emca-mobile-actions">
                            <x-locale-switcher tone="marketing" />
                            @guest
                                <a href="{{ route('login') }}" class="theme-btn btn-style-two">{{ __('Sign in') }}</a>
                                <a href="{{ route('register') }}" class="theme-btn btn-style-two">{{ __('Register') }}</a>
                            @else
                                <a href="{{ route('dashboard') }}" class="theme-btn btn-style-two">{{ __('Dashboard') }}</a>
                            @endguest
                        </div>
                    </nav>
                </div>

                <div class="sticky-header">
                    <div class="auto-container">
                        <div class="inner-container">
                            <div class="logo">
                                <a href="{{ route('home') }}"><img src="{{ $logo }}" alt="Jumuika Pass"></a>
                            </div>
                            <div class="nav-outer">
                                <nav class="main-menu">
                                    <div class="navbar-collapse show collapse clearfix">
                                        <ul class="navigation clearfix"></ul>
                                    </div>
                                </nav>
                                <div class="mobile-nav-toggler">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                            <a href="{{ $supportPhoneLink }}" class="header-phone_box">
                                <span class="icon"><i aria-hidden="true" class="fas fa-phone-alt"></i></span>
                                <span class="info">
                                    {{ __('Call us') }}
                                    <strong>{{ config('emca.support_phone') }}</strong>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div data-emca-sync="flash">
                @if (session('status') || session('error'))
                    <div @class(['emca-flash', 'emca-flash-error' => session('error')]) role="status">
                        <i @class(['fa-solid', 'fa-circle-exclamation' => session('error'), 'fa-circle-check' => ! session('error')])></i>
                        <span>{{ session('error') ?? session('status') }}</span>
                        <button type="button" class="emca-flash-close" aria-label="{{ __('Close') }}"><i class="fa-regular fa-xmark"></i></button>
                    </div>
                @endif
            </div>

            @yield('content')

            <footer class="main-footer footer-style-one">
                <div class="outer-box">
                    <div class="auto-container">
                        <div class="widgets-section">
                            <div class="row gx-4">
                                <div class="footer-column col-lg-3 col-md-6">
                                    <div class="footer-widget about-widget wow fadeInUp" data-wow-delay="200ms">
                                        <div class="h4 widget-title">{{ config('app.name') }}</div>
                                        <div class="widget-content">
                                            <div class="text">{{ __('Digital invitation cards for weddings, send-offs, kitchen parties, and the guest lists you need to count. Replies, pledges, and check-in stay together.') }}</div>
                                            <x-site-button :href="route('home').'#demo'">{{ __('Book a demo') }}</x-site-button>
                                        </div>
                                    </div>
                                </div>
                                <div class="footer-column col-lg-3 col-md-6">
                                    <div class="footer-widget links-widget style-one wow fadeInUp" data-wow-delay="400ms">
                                        <div class="h4 widget-title">{{ __('Explore') }}</div>
                                        <div class="widget-content">
                                            <ul class="user-links">
                                                <li><a href="{{ route('templates.index') }}">{{ __('Templates') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                                <li><a href="{{ route('features') }}">{{ __('Features') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                                <li><a href="{{ route('pricing') }}">{{ __('Pricing') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                                <li><a href="{{ route('events.upcoming') }}">{{ __('Upcoming events') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                                <li><a href="{{ route('privacy') }}">{{ __('Privacy policy') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                                <li><a href="{{ route('terms') }}">{{ __('Terms and conditions') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="footer-column col-lg-3 col-md-6">
                                    <div class="footer-widget links-widget style-two wow fadeInUp" data-wow-delay="600ms">
                                        <div class="h4 widget-title">{{ __('What hosts use') }}</div>
                                        <div class="widget-content">
                                            <ul class="user-links">
                                                <li><a href="{{ route('features') }}">{{ __('Card design') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                                <li><a href="{{ route('features') }}">{{ __('WhatsApp and SMS') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                                <li><a href="{{ route('features') }}">{{ __('RSVP and headcount') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                                <li><a href="{{ route('features') }}">{{ __('One-time check-in') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                                <li><a href="{{ route('features') }}">{{ __('Pledges and budget') }}<i class="tm-nav-arrow-icon fas fa-circle"></i></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="footer-column col-lg-3 col-md-6">
                                    <div class="footer-widget contact-widget wow fadeInUp" data-wow-delay="800ms">
                                        <div class="info">
                                            <div class="h4 widget-title">{{ __('Address') }}</div>
                                            <div class="text">{{ config('emca.support_address') }}</div>
                                        </div>
                                        <div class="info style-two">
                                            <div class="h4 widget-title">{{ __('Talk to us') }}</div>
                                            <span><a href="{{ $supportPhoneLink }}">{{ config('emca.support_phone') }}</a></span>
                                            <span><a href="mailto:{{ config('emca.support_email') }}">{{ config('emca.support_email') }}</a></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="footer-middle-box">
                            <div class="inner-box">
                                <div class="left-box wow fadeInLeft" data-wow-delay="400ms">
                                    <div class="logo"><a href="{{ route('home') }}"><img class="emca-logo-light" src="{{ $logo }}" alt="Jumuika Pass"></a></div>
                                    <ul class="social-icon">
                                        <li><a href="https://wa.me/{{ preg_replace('/\D+/', '', config('emca.support_phone')) }}" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a></li>
                                        <li><a href="{{ $supportPhoneLink }}" aria-label="{{ __('Phone') }}"><i class="fa-solid fa-phone"></i></a></li>
                                        <li><a href="mailto:{{ config('emca.support_email') }}" aria-label="{{ __('Email') }}"><i class="fa-solid fa-envelope"></i></a></li>
                                    </ul>
                                </div>
                                <div class="right-box wow fadeInRight" data-wow-delay="400ms">
                                    <div class="h5 title">{{ __('Ready to send') }} <br>{{ __('your first card?') }}</div>
                                    <x-site-button :href="auth()->check() ? route('events.index', ['new' => 1]) : route('register')">{{ __('Launch platform') }}</x-site-button>
                                </div>
                            </div>
                        </div>
                        <div class="footer-bottom">
                            <div class="inner-container">
                                <p class="copyright-text">&copy; {{ now()->year }} {{ config('app.name') }}. {{ __('All rights reserved.') }}</p>
                                <ul class="footer-nav">
                                    <li><a href="{{ route('privacy') }}">{{ __('Privacy policy') }}</a></li>
                                    <li><a href="{{ route('terms') }}">{{ __('Terms and conditions') }}</a></li>
                                    <li><a href="{{ route('pricing') }}">{{ __('Pricing') }}</a></li>
                                    <li><a href="{{ route('home') }}#demo">{{ __('Talk to us') }}</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        <script src="{{ $insectra }}/js/jquery.js"></script>
        <script src="{{ $insectra }}/js/popper.min.js"></script>
        <script src="{{ $insectra }}/js/bootstrap.min.js"></script>
        <script src="{{ $insectra }}/js/jquery.fancybox.js"></script>
        <script src="{{ $insectra }}/js/jquery-ui.js"></script>
        <script src="{{ $insectra }}/js/wow.js"></script>
        <script src="{{ $insectra }}/js/select2.min.js"></script>
        <script src="{{ $insectra }}/js/appear.js"></script>
        <script src="{{ $insectra }}/js/bxslider.js"></script>
        <script src="{{ $insectra }}/js/knob.js"></script>
        <script src="{{ $insectra }}/js/swiper.min.js"></script>
        <script src="{{ $insectra }}/js/aos.js"></script>
        <script src="{{ $insectra }}/js/gsap.min.js"></script>
        <script src="{{ $insectra }}/js/ScrollTrigger.min.js"></script>
        <script src="{{ $insectra }}/js/splitType.js"></script>
        <script src="{{ $insectra }}/js/gsap-scroll-smoother.js"></script>
        <script src="{{ $insectra }}/js/gsap-scroll-to-plugin.js"></script>
        <script src="{{ $insectra }}/js/SplitText.min.js"></script>
        <script src="{{ $insectra }}/js/custom-gsap.js"></script>
        <script src="{{ $insectra }}/js/script.js"></script>
        <script>
            document.addEventListener('click', function (event) {
                document.querySelectorAll('.emca-locale[open]').forEach(function (menu) {
                    if (! menu.contains(event.target)) {
                        menu.removeAttribute('open');
                    }
                });

                if (event.target.closest('.emca-flash-close')) {
                    event.target.closest('.emca-flash').remove();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('.emca-locale[open]').forEach(function (menu) {
                    menu.removeAttribute('open');
                });
            });
        </script>
        @stack('scripts')
        @include('layouts.partials.ajax-script')
    </body>
</html>
