@extends('layouts.marketing')

@section('title', config('app.name').' | '.__('Digital invitation cards'))

@php
    $launch = auth()->check() ? route('events.index', ['new' => 1]) : route('register');
    $occasionLabel = fn (string $slug): string => \App\Models\EventCategory::findBySlug($slug)?->label() ?? __('labels.event_types.'.$slug);
    $occasions = [
        [$occasionLabel('wedding'), 'occasion-wedding.jpg', __('A card with both names, the hall, and a reply link for every seat.')],
        [$occasionLabel('sendoff'), 'occasion-sendoff.jpg', __('Share the programme and know who is actually coming.')],
        [$occasionLabel('kitchen_party'), 'occasion-kitchen-party.jpg', __('Keep the smaller list, the contributions, and the reminders together.')],
        [$occasionLabel('birthday'), 'occasion-birthday.jpg', __('A short card, a headcount, and a door that does not take the same code twice.')],
        [__('Celebrations'), 'occasion-celebration.jpg', __('Anniversaries, galas, and family days use the same guest list.')],
        [__('Meetings'), 'occasion-meeting.jpg', __('Corporate gatherings and contribution meetings, with a record of who arrived.')],
    ];
    $occasionCategories = \App\Models\EventCategory::options();
    $platformBlocks = [
        ['fa-palette', __('Card design'), __('Start from a template, then put the hosts, date, venue, and dress on the card.'), 'service-1.jpg'],
        ['fa-paper-plane', __('Private links'), __('Every guest has a card number and a link. Replies stay attached to that person.'), 'service-2.jpg'],
        ['fa-users', __('RSVP and seats'), __('See who is still waiting, who is attending, and who has declined, plus how many plates that means.'), 'service-3.jpg'],
        ['fa-comment-sms', __('Messages'), __('Prepare invitations, reminders, and thank-you notes, then send them from the guest row.'), 'service-4.jpg'],
        ['fa-qrcode', __('One-time door'), __('Scan the code or type the number. Once a card is used, it cannot admit anyone else.'), 'service-5.jpg'],
        ['fa-hand-holding-dollar', __('Pledges and tasks'), __('Record contributions, the budget, vendors, and what is still open before the day.'), 'service-6.jpg'],
    ];
    $questions = [
        [__('Does every guest need a smartphone?'), __('No. Guests with smartphones receive the card on WhatsApp. Everyone else receives a text with the card number, and that number still works at the door.')],
        [__('What stops a forwarded card from being used twice?'), __('Each guest has their own card number. Once it is scanned or typed at the door, a second attempt shows that the card was already used.')],
        [__('Do guests need an account to reply?'), __('No. Each guest opens their private card link, says whether they will attend, and how many people are coming.')],
        [__('How do I bring in a long guest list?'), __('Import a CSV file with names and phone numbers instead of typing the list one row at a time.')],
        [__('What currency are the prices in?'), __('Prices are in Tanzanian shillings. Choose the plan that matches the number of guests you need to invite.')],
    ];
    $steps = [
        ['fa-envelope-open-text', __('Create the event'), __('Pick a card template, add the hosts, date, and venue, then import or type your guest list.')],
        ['fa-paper-plane', __('Send the cards'), __('Each guest receives a private card link on WhatsApp, or a text with the card number.')],
        ['fa-qrcode', __('Check guests in'), __('At the door, scan the code or type the number. Each card admits one entry.')],
    ];
@endphp

@section('content')
    <section class="banner-section fix">
        <div class="outer-box">
            <div class="bg bg-image"><img src="{{ asset('images/home/hero.jpg') }}" alt="{{ __('Guests at a celebration') }}"></div>
            <div class="outer-container">
                <div class="inner-container">
                    <div class="banner-content wow fadeInUp" data-wow-delay="200ms">
                        <h1 class="h1 banner-title">{{ __('Send the card.') }}</h1>
                        <div class="text wow fadeInUp" data-wow-delay="500ms">{{ __('Emca is for hosts who used to print cards, follow up for replies, and guess the headcount. Each guest receives a private card on WhatsApp or by SMS. The number on that card works once at the door.') }}</div>
                    </div>
                    <div class="content-box">
                        <div class="h2 count-box wow fadeInUp" data-wow-delay="200ms"><span class="count-text" data-speed="2000" data-stop="{{ $occasionCategories->count() }}">0</span>+</div>
                        <div class="info wow fadeInUp" data-wow-delay="400ms">
                            <div class="image emca-hero-avatars" aria-hidden="true">
                                <img src="{{ asset('images/home/avatar-1.jpg') }}" alt="">
                                <img src="{{ asset('images/home/avatar-2.jpg') }}" alt="">
                                <span><i class="fa-light fa-plus"></i></span>
                            </div>
                            <div class="text">{{ __('Kinds of occasions you can host') }}</div>
                        </div>
                        <x-site-button :href="$launch" class="wow fadeInUp" data-wow-delay="600ms">{{ __('Launch platform') }}</x-site-button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="brand-section">
        <div class="outer-container">
            <div class="inner-container">
                <div class="swiper brand-slider wow fadeInUp pb-0">
                    <div class="swiper-wrapper">
                        @for ($pass = 0; $pass < 2; $pass++)
                            @foreach ($occasionCategories as $occasionCategory)
                                <div class="swiper-slide">
                                    <div class="emca-ticker-item"><i class="fa-light {{ $occasionCategory->iconClass() }}"></i> {{ $occasionCategory->label() }}</div>
                                </div>
                            @endforeach
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-section fix section-padding">
        <div class="auto-container">
            <div class="row align-items-center gx-3">
                <div class="image-column col-xl-6 col-lg-8">
                    <div class="inner-column">
                        <div class="round-text">
                            <div class="shape"><i class="fa-solid fa-envelope-open-text"></i></div>
                            <span>
                                <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <defs>
                                        <path id="circlePath" d="M100,100 m-80,0 a80,80 0 1,1 160,0 a80,80 0 1,1 -160,0"></path>
                                    </defs>
                                    <text letter-spacing="6" text-anchor="middle">
                                        <textPath href="#circlePath" startOffset="50%">{{ mb_strtoupper(__('Digital invitation cards')) }} &middot; {{ mb_strtoupper(__('One-time check-in')) }} &middot;</textPath>
                                    </text>
                                </svg>
                            </span>
                        </div>
                        <div class="image-box one wow fadeInUp" data-wow-delay="200ms">
                            <div class="image"><img src="{{ asset('images/home/about-1.jpg') }}" alt="{{ __('Printed and written invitation cards on a table') }}"></div>
                        </div>
                        <div class="image-box two wow fadeInUp" data-wow-delay="400ms">
                            <div class="image"><img src="{{ asset('images/home/about-2.jpg') }}" alt="{{ __('A guest showing her invitation on a phone') }}"></div>
                            <div class="image"><img src="{{ asset('images/home/about-3.jpg') }}" alt="{{ __('A couple during a celebration') }}"></div>
                        </div>
                    </div>
                </div>
                <div class="content-column col-xl-6 col-lg-8">
                    <div class="inner-column">
                        <div class="sec-title">
                            <div class="h6 sub-title">{{ __('Why hosts switch') }}</div>
                            <h2 class="h2 title char-animation">{{ __('The invitation still works after you send it') }}</h2>
                            <div class="text">{{ __('A printed card looks finished, then it disappears into a pile. Emca keeps the design, the reply, the pledge, and the door record on the same guest. You know the headcount before you order food, and a shared photo cannot admit a second person.') }}</div>
                        </div>
                        <div class="info-box">
                            <div class="info wow fadeInUp" data-wow-delay="200ms">
                                <div class="icon"><i aria-hidden="true" class="fa-light fa-mobile-screen"></i></div>
                                <div class="content">
                                    <div class="h5 title">{{ __('Reach every phone') }}</div>
                                    <div class="text">{{ __('WhatsApp for smartphones, SMS for everyone else.') }}</div>
                                </div>
                            </div>
                            <div class="info wow fadeInUp" data-wow-delay="400ms">
                                <div class="icon"><i aria-hidden="true" class="fa-light fa-wallet"></i></div>
                                <div class="content">
                                    <div class="h5 title">{{ __('Budget from real numbers') }}</div>
                                    <div class="text">{{ __('Confirmed seats and pledges beside the list.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="bottom-box wow fadeInUp" data-wow-delay="600ms">
                            <x-site-button :href="route('features')">{{ __('See the features') }}</x-site-button>
                            <div class="call-info">
                                <div class="icon"><i aria-hidden="true" class="fa-solid fa-phone"></i></div>
                                <div class="info">
                                    <div class="text">{{ __('Talk to us') }}:</div>
                                    <div class="h5 title"><a href="tel:{{ preg_replace('/\s+/', '', config('emca.support_phone')) }}">{{ config('emca.support_phone') }}</a></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="services-section" id="service-sec">
        <div class="outer-box section-padding">
            <div class="auto-container">
                <div class="row justify-content-center">
                    <div class="sec-title text-center col-lg-7 mb-20">
                        <div class="h6 sub-title bg-color">{{ __('From the first card to the last guest') }}</div>
                        <h2 class="h2 title char-animation">{{ __('What the platform keeps track of') }}</h2>
                    </div>
                </div>
            </div>
            <div class="service-outer-container">
                @foreach ($platformBlocks as $index => [$icon, $title, $copy, $photo])
                    <div class="service-block">
                        <div class="auto-container">
                            <div class="row g-4 align-items-center">
                                <div class="col-xl-4 col-lg-3 col-md-6">
                                    <div class="content">
                                        <div class="icon">
                                            <div class="service-icon"><i aria-hidden="true" class="fa-light {{ $icon }}"></i></div>
                                        </div>
                                        <h3 class="h3 service-title"><a href="{{ route('features') }}">{{ $title }}</a></h3>
                                    </div>
                                </div>
                                <div class="col-xl-4 col-lg-4 col-md-6">
                                    <div class="service-image">
                                        <img src="{{ asset('images/home/'.$photo) }}" alt="">
                                        <img src="{{ asset('images/home/'.$photo) }}" alt="{{ $title }}">
                                        <span class="number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}<b>.</b></span>
                                    </div>
                                </div>
                                <div class="col-xl-4 col-lg-5 col-md-12">
                                    <div class="content-2">
                                        <div class="service-details">{{ $copy }}</div>
                                        <a href="{{ route('features') }}" class="link-btn">{{ __('See the features') }} <i class="fa fa-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="why-choose-us-section section-padding fix">
        <div class="auto-container">
            <div class="sec-title-box mb-20">
                <div class="sec-title">
                    <div class="h6 sub-title">{{ __('What hosts use') }}</div>
                    <h2 class="h2 title char-animation">{{ __('Everything a host needs from a digital invitation') }}</h2>
                </div>
                <div class="text">{{ __('Hosts already send cards on WhatsApp and collect pledges by phone, then worry that a forwarded photo will be used at the gate. Emca keeps the guest list, the messages, and the pledges in one place, and gives each guest a number that can be scanned once.') }}</div>
            </div>
            <div class="row align-items-center">
                <div class="col-xl-7 col-lg-6 col-md-8 wow fadeInUp" data-wow-delay=".3s">
                    <div class="choose-us-block1">
                        <div class="thumb">
                            <img src="{{ asset('images/home/choose-us.jpg') }}" alt="{{ __('Guests at a celebration') }}">
                        </div>
                    </div>
                </div>
                <div class="col-xl-5 col-lg-6 wow fadeInUp" data-wow-delay=".5s">
                    <ul class="choose-us-block-content1">
                        @foreach ([
                            ['fa-brands fa-whatsapp', __('WhatsApp and SMS'), __('Guests with smartphones receive the card on WhatsApp. Everyone else receives a text with the card number. The same details stay on the card page.')],
                            ['fa-light fa-users', __('RSVP and headcount'), __('Waiting, attending, and declined stay visible, with the number of people attached to each yes.')],
                            ['fa-light fa-qrcode', __('One-time check-in'), __('The door team scans the code or types the card number. A second attempt shows that the card was already used.')],
                            ['fa-light fa-hand-holding-dollar', __('Pledges and budget'), __('Confirmed seats, promised pledges, and money already received sit beside the guest list.')],
                        ] as [$icon, $title, $copy])
                            <li>
                                <div class="icon"><i aria-hidden="true" class="{{ $icon }}"></i></div>
                                <div class="content">
                                    <div class="h4 title">{{ $title }}</div>
                                    <div class="text">{{ $copy }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="video-section">
        <div class="outer-box fix">
            <div class="bg-image"><img src="{{ asset('images/home/fun-facts.jpg') }}" alt=""></div>
            <div class="auto-container">
                <div class="row g-4">
                    @foreach ([
                        ['fa-qrcode', 1, '', __('Entry per card number')],
                        ['fa-comment-sms', 2, '', __('Ways to deliver: WhatsApp and SMS')],
                        ['fa-tags', count(config('emca.plans')), '', __('Plans by the size of the guest list')],
                        ['fa-champagne-glasses', $occasionCategories->count(), '', __('Kinds of occasions you can host')],
                    ] as $index => [$icon, $number, $suffix, $label])
                        <div class="funfact-box col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="{{ ($index + 1) * 200 }}ms">
                            <div class="inner-box">
                                <div class="icon"><i aria-hidden="true" class="fa-light {{ $icon }}"></i></div>
                                <div class="content">
                                    <div class="h2 count-box"><span class="count-text" data-speed="2000" data-stop="{{ $number }}">0</span>{{ $suffix }}</div>
                                    <div class="text">{{ $label }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="features-section fix section-padding">
        <div class="auto-container">
            <div class="row justify-content-center">
                <div class="sec-title text-center col-lg-6 mb-50">
                    <div class="h6 sub-title">{{ __('Occasions') }}</div>
                    <h2 class="h2 title char-animation">{{ __('Occasions we design cards for') }}</h2>
                </div>
            </div>
            <div class="row">
                @foreach ($occasions as $index => [$title, $photo, $copy])
                    <div class="features-block col-xl-4 col-md-6 wow fadeInUp" data-wow-delay="{{ (($index % 3) + 1) * 200 }}ms">
                        <div class="image-box">
                            <div class="image">
                                <img src="{{ asset('images/home/'.$photo) }}" alt="{{ $title }}">
                            </div>
                        </div>
                        <div class="content">
                            <h3 class="h4 features-title"><a href="{{ route('templates.index') }}">{{ $title }}</a></h3>
                            {{ $copy }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="faq-section">
        <div class="outer-box fix section-padding">
            <div class="auto-container">
                <div class="row">
                    <div class="content-column col-lg-5">
                        <div class="inner-column">
                            <div class="sec-title mb-50">
                                <div class="h6 sub-title bg-color">{{ __('Questions') }}</div>
                                <h2 class="h2 title char-animation">{{ __('Frequently asked') }} <span>{{ __('questions') }}</span></h2>
                            </div>
                            <div class="image"><img src="{{ asset('images/home/faq.jpg') }}" alt=""></div>
                            <div class="bottom-box">
                                <div class="h5 title">{{ __('Have a question') }} <br>{{ __('on your mind?') }}</div>
                                <x-site-button href="#demo">{{ __('Talk to us') }}</x-site-button>
                            </div>
                        </div>
                    </div>
                    <div class="faq-column col-lg-7">
                        <div class="inner-column">
                            <div class="faq-box">
                                <div class="inner-box">
                                    <ul class="accordion-box">
                                        @foreach ($questions as $index => [$question, $answer])
                                            <li @class(['accordion block wow fadeInUp', 'active-block' => $index === 1]) data-wow-delay="{{ ($index + 2) * 100 }}ms">
                                                <div @class(['acc-btn', 'active' => $index === 1])>{{ $question }}
                                                    <i class="icon fa-solid fa-plus"></i>
                                                </div>
                                                <div @class(['acc-content', 'current' => $index === 1])>
                                                    <div class="content">
                                                        <div class="text">{{ $answer }}</div>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="working-section fix section-padding">
        <div class="auto-container">
            <div class="row justify-content-center">
                <div class="sec-title text-center col-lg-7 mb-60">
                    <div class="h6 sub-title">{{ __('How it works') }}</div>
                    <h2 class="h2 title char-animation">{{ __('From the guest list to the door in three steps') }}</h2>
                </div>
            </div>
            <div class="row gx-4">
                @foreach ($steps as $index => [$icon, $title, $copy])
                    <div class="col-xl-4 col-md-6 wow fadeInUp" data-wow-delay="{{ ($index + 1) * 200 }}ms">
                        <div class="working-block-style2">
                            <div class="number-top">
                                <span class="number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <div class="shape-style1"><i class="fa-light {{ $icon }}"></i></div>
                            </div>
                            <div class="content">
                                <h3 class="h4 working-title">{{ $title }}</h3>
                                <div class="working-details">{{ $copy }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="bottom-box wow fadeInUp" data-wow-delay="800ms">
                    <div class="inner-box">
                        <div class="left-box">
                            <div class="image"><i class="fa-solid fa-heart"></i></div>
                            <div class="h5 title">{{ __('Ready to send the first card for your event?') }}</div>
                        </div>
                        <div class="right-box">
                            <x-site-button :href="$launch">{{ __('Launch platform') }}</x-site-button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonial-section">
        <div class="outer-box fix section-padding">
            <div class="bg bg-image"><img src="{{ asset('images/home/upcoming-bg.jpg') }}" alt=""></div>
            <div class="auto-container">
                <div class="row">
                    <div class="image-column col-lg-5 col-md-8">
                        <div class="inner-column">
                            <div class="image wow fadeInLeft" data-wow-delay="200ms"><img src="{{ asset('images/home/upcoming.jpg') }}" alt="{{ __('Guests at a celebration') }}"></div>
                            <div class="info-box wow fadeInLeft" data-wow-delay="400ms">
                                <div class="text">{{ __('Events that hosts have chosen to list publicly.') }}</div>
                                <div class="line"></div>
                                <div class="author-image"><i class="fa-solid fa-calendar-check"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="content-column col-lg-7">
                        <div class="inner-column">
                            <div class="sec-title light mb-40">
                                <div class="h6 sub-title">{{ __('Public list') }}</div>
                                <h2 class="h2 title char-animation">{{ __('Upcoming events') }}</h2>
                            </div>
                            <div class="swiper-container testimonial-one-slider pb-0 overflow-hidden wow fadeInUp">
                                <div class="swiper-wrapper">
                                    @forelse ($upcomingEvents as $event)
                                        <div class="testimonial-block swiper-slide">
                                            <div class="inner-block">
                                                <div class="top-box">
                                                    <div class="h6 mb-0 text-white">{{ $event->type->label() }}</div>
                                                </div>
                                                <div class="text">{{ $event->title }}</div>
                                                <ul class="emca-event-meta">
                                                    <li><i class="fa-light fa-calendar"></i> {{ $event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A') }}</li>
                                                    <li><i class="fa-light fa-location-dot"></i> {{ $event->venue }}</li>
                                                </ul>
                                                <div class="author-info">
                                                    <div class="info">
                                                        <div class="h4 testimonial-name">{{ $event->hostsLabel() }}</div>
                                                        <div class="testimonial-position">{{ __('Hosts') }}</div>
                                                    </div>
                                                    <div class="quote-icon"><i class="fa-light fa-envelope-open-text"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="testimonial-block swiper-slide">
                                            <div class="inner-block">
                                                <div class="text">{{ __('No public events are listed yet.') }}</div>
                                                <div class="author-info">
                                                    <div class="info">
                                                        <div class="h4 testimonial-name">{{ __('Check back soon') }}</div>
                                                    </div>
                                                    <div class="quote-icon"><i class="fa-light fa-calendar"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                                <div class="testimonial-one-dots"></div>
                            </div>
                            <div class="mt-40">
                                <x-site-button :href="route('events.upcoming')" class="is-light">{{ __('View the list') }}</x-site-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="blog-section section-padding fix">
        <div class="auto-container">
            <div class="row justify-content-center">
                <div class="sec-title col-lg-8 text-center mb-30">
                    <div class="h6 sub-title">{{ __('Library') }}</div>
                    <h2 class="h2 title char-animation">{{ __('Card templates') }}</h2>
                </div>
            </div>
            <div class="row gx-4">
                @forelse ($templates as $index => $template)
                    <div class="col-xl-4 col-md-6">
                        @include('templates.partials.card', ['template' => $template, 'delay' => (($index % 3) + 1) * 200])
                    </div>
                @empty
                    <div class="col-12 text-center"><p>{{ __('Templates appear here once they are added to the library.') }}</p></div>
                @endforelse
            </div>
            <div class="text-center mt-50">
                <x-site-button :href="route('templates.index')">{{ __('See all') }}</x-site-button>
            </div>
        </div>
    </section>

    @include('pages.partials.demo-form')
@endsection
