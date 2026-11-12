@extends('layouts.marketing')

@section('title', __('Upcoming events').' | '.config('app.name'))

@section('content')
    @include('layouts.partials.breadcrumb', [
        'title' => __('Upcoming events'),
        'photo' => 'page-titles/upcoming.jpg',
        'lead' => __('Events that hosts have chosen to list publicly.'),
    ])

    <section class="blog-section section-padding fix">
        <div class="auto-container">
            <div class="row gx-4">
                @forelse ($events as $index => $event)
                    <div class="col-xl-4 col-md-6">
                        <article class="blog-block emca-event-card wow fadeInUp" data-wow-delay="{{ (($index % 3) + 1) * 200 }}ms">
                            <div class="thumb">
                                <img src="{{ $event->type?->photoUrl() ?? asset('images/occasions/'.\App\Models\EventCategory::DefaultPhoto) }}" alt="">
                            </div>
                            <div class="content">
                                <span class="tag">{{ $event->type?->label() }}</span>
                                <h2 class="h4 entry-title">{{ $event->title }}</h2>
                                <p class="emca-event-hosts">{{ $event->hostsLabel() }}</p>
                                <p class="emca-event-headline">{{ $event->cardHeadline() }}</p>
                                <ul class="emca-event-details">
                                    <li><i class="fa-light fa-calendar"></i> {{ $event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A') }}</li>
                                    <li><i class="fa-light fa-location-dot"></i> {{ $event->venue }}</li>
                                    @if ($event->dress_code)
                                        <li><i class="fa-light fa-shirt"></i> {{ __('Dress: :code', ['code' => $event->dress_code]) }}</li>
                                    @endif
                                </ul>
                                @if ($event->message)
                                    <div class="mascot-post-excerpt">{{ $event->message }}</div>
                                @endif
                                <p class="emca-event-owner">{{ __('Hosted by :name', ['name' => $event->user->name]) }}</p>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="emca-panel text-center">{{ __('No upcoming public events.') }}</div>
                    </div>
                @endforelse
            </div>
            <div class="mt-40">{{ $events->links('pagination::bootstrap-5') }}</div>
        </div>
    </section>
@endsection
