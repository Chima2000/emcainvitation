@extends('layouts.marketing')

@section('title', $heading.' | '.config('app.name'))

@section('content')
    @include('layouts.partials.breadcrumb', ['title' => $heading, 'photo' => $photo])

    <section class="section-padding fix emca-error">
        <div class="auto-container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="emca-panel error-page__inner wow fadeInUp" data-wow-delay="200ms">
                        <span class="emca-error-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                        <div class="error-page__title-box">
                            <div class="error-page__title" aria-hidden="true">{{ $code }}</div>
                            <h2 class="h3 error-page__sub-title">{{ $subtitle }}</h2>
                        </div>
                        <p class="error-page__text">{{ $text }}</p>
                        <div class="emca-error-actions">
                            @yield('actions')
                        </div>

                        <div class="emca-error-links">
                            <p class="emca-error-links-title">{{ __('Popular pages') }}</p>
                            <nav class="emca-filter" aria-label="{{ __('Popular pages') }}">
                                <a href="{{ route('templates.index') }}">{{ __('Templates') }}</a>
                                <a href="{{ route('features') }}">{{ __('Features') }}</a>
                                <a href="{{ route('events.upcoming') }}">{{ __('Upcoming events') }}</a>
                                <a href="{{ route('pricing') }}">{{ __('Pricing') }}</a>
                                @auth
                                    <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                                @endauth
                            </nav>
                            <p class="emca-error-help">
                                {{ __('Still stuck? Call us on') }}
                                <a href="tel:{{ preg_replace('/\s+/', '', config('emca.support_phone')) }}">{{ config('emca.support_phone') }}</a>
                                {{ __('or email') }}
                                <a href="mailto:{{ config('emca.support_email') }}">{{ config('emca.support_email') }}</a>.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
