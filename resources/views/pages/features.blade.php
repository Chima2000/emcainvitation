@extends('layouts.marketing')

@section('title', __('Features').' | '.config('app.name'))

@section('content')
    @include('layouts.partials.breadcrumb', ['title' => __('Features'), 'photo' => 'page-titles/features.jpg'])

    <section class="about-section emca-intro section-padding fix">
        <div class="auto-container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 wow fadeInLeft" data-wow-delay="200ms">
                    <div class="emca-intro-image">
                        <img src="{{ asset('images/features/intro.jpg') }}" alt="{{ __('A wedding celebration') }}">
                    </div>
                </div>
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="300ms">
                    <div class="sec-title mb-30">
                        <div class="h6 sub-title">{{ __('What hosts use') }}</div>
                        <h2 class="h2 title char-animation">{{ __('Everything a host needs from a digital invitation') }}</h2>
                    </div>
                    <p class="text">{{ __('Hosts already send cards on WhatsApp and collect pledges by phone, then worry that a forwarded photo will be used at the gate. Emca keeps the guest list, the messages, and the pledges in one place, and gives each guest a number that can be scanned once.') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="services-section-two emca-feature-grid pb-120 fix">
        <div class="outer-box">
            <div class="auto-container">
                <div class="row g-4">
                    @foreach ([
                        ['fa-envelope-open-text', 'invitation-cards.jpg', __('Invitation cards'), __('Pick a template for the occasion, then write the names, place, and time guests will see on their phone.')],
                        ['fa-comment-sms', 'whatsapp-sms.jpg', __('WhatsApp and SMS'), __('Prepare the message in the event, then open WhatsApp or the phone\'s SMS app beside that guest. Feature phones still receive the card number.')],
                        ['fa-users', 'rsvp-headcount.jpg', __('RSVP and headcount'), __('Waiting, attending, and declined stay visible, with the number of people attached to each yes.')],
                        ['fa-qrcode', 'check-in.jpg', __('One-time check-in'), __('The door team scans the code or types the card number. A second attempt shows that the card was already used.')],
                        ['fa-bell', 'reminders.jpg', __('Reminders and thanks'), __('Nudge guests who have not replied, then send a thank-you after they have been checked in.')],
                        ['fa-hand-holding-dollar', 'pledges.jpg', __('Pledges'), __('Record what was promised and what has been received, optionally against a named guest.')],
                        ['fa-wallet', 'budget.jpg', __('Budget'), __('Income and expenses sit in one list so the balance is visible before the day.')],
                        ['fa-list-check', 'tasks-vendors.jpg', __('Tasks and vendors'), __('Track what is still open, and which caterers, halls, and photographers are booked.')],
                        ['fa-file-import', 'guest-import.jpg', __('Guest import'), __('Bring a spreadsheet of names and phone numbers instead of typing the list one row at a time.')],
                    ] as $index => [$icon, $photo, $title, $copy])
                        <div class="col-xl-4 col-md-6 wow fadeInUp" data-wow-delay="{{ (($index % 3) + 1) * 150 }}ms">
                            <div @class(['service-block-two', 'active' => $index === 1])>
                                <div class="content">
                                    <div class="icon">
                                        <div class="service-icon"><i aria-hidden="true" class="fa-light {{ $icon }}"></i></div>
                                    </div>
                                    <h3 class="h4 service-title">{{ $title }}</h3>
                                    <p class="emca-service-text">{{ $copy }}</p>
                                </div>
                                <div class="thumb">
                                    <img src="{{ asset('images/features/'.$photo) }}" alt="">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-60">
                    <x-site-button :href="route('pricing')">{{ __('See pricing') }}</x-site-button>
                </div>
            </div>
        </div>
    </section>
@endsection
