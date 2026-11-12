@extends('layouts.marketing')

@section('title', __('Pricing').' | '.config('app.name'))

@section('content')
    @include('layouts.partials.breadcrumb', ['title' => __('Pricing'), 'photo' => 'page-titles/pricing.jpg'])

    <section class="pricing-section-inner emca-pricing fix section-padding">
        <div class="auto-container">
            <div class="row justify-content-center">
                <div class="sec-title text-center col-lg-7 mb-30">
                    <div class="h6 sub-title">{{ __('Pricing') }}</div>
                    <h2 class="h2 title char-animation">{{ __('Choose by the size of the list') }}</h2>
                    <p class="text mt-20">{{ __('Prices are in Tanzanian shillings. Top up your wallet with M-Pesa, Airtel Money, Mixx by Yas or bank, then pick a package or pay per message.') }}</p>
                </div>
            </div>
            <div class="row g-4 justify-content-center">
                @foreach ($packages as $package)
                    <div class="col-xl-4 col-md-6 wow fadeInUp" data-wow-delay="{{ ($loop->index + 1) * 200 }}ms">
                        <div @class(['pricing-block-items-1', 'active' => $package->is_featured])>
                            <div class="pricing-header">
                                <div class="content">
                                    <h2 class="h4 title">{{ $package->name }}</h2>
                                    @if ($package->summary)
                                        <p>{{ $package->summary }}</p>
                                    @endif
                                </div>
                                <div class="h2 pricing-title"><sub>TZS</sub> {{ number_format($package->price) }}</div>
                                <p class="emca-plan-range">{{ __('Up to :count guests', ['count' => number_format($package->guest_limit)]) }}</p>
                            </div>
                            <div class="pricing-list-item">
                                <ul>
                                    @foreach ($package->inclusions() as $item)
                                        <li><i class="fa-solid fa-circle-check"></i> {{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <x-site-button :href="route('register')">{{ __('Get started') }}</x-site-button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row justify-content-center mt-5">
                <div class="col-lg-8">
                    <div class="pricing-block-items-1 emca-payg">
                        <div class="pricing-header">
                            <div class="content">
                                <h2 class="h4 title">{{ __('Pay as you go') }}</h2>
                                <p>{{ __('No package needed. Each message is paid from your wallet, and sending pauses when the balance runs low.') }}</p>
                            </div>
                        </div>
                        <div class="pricing-list-item">
                            <ul>
                                @foreach ($items as $item)
                                    <li class="d-flex justify-content-between gap-3">
                                        <span><i class="fa-solid fa-circle-check"></i> {{ $item->label() }}</span>
                                        <strong>TZS {{ number_format($rates[$item->value]) }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <x-site-button :href="route('home').'#demo'">{{ __('Book a demo') }}</x-site-button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
