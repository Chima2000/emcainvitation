@extends('layouts.dashboard')

@section('title', __('Check-in'))
@section('heading', $checkedIn ? __('Checked in') : __('Already used'))
@section('subheading')
    <a href="{{ route('events.show', $event) }}#check-in">{{ __('Back to :title', ['title' => $event->title]) }}</a>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="mb-2">{{ $guest->name }}</h2>
                    <p class="font-monospace mb-3">{{ $guest->serial }}</p>
                    <p class="text-body mb-2">
                        @if ($checkedIn)
                            {{ __('This card is now used. It cannot admit anyone else.') }}
                        @else
                            {{ __('This card was already used') }}
                            @if ($guest->checked_in_at)
                                {{ __('at :time', ['time' => $guest->checked_in_at->timezone(config('app.timezone'))->translatedFormat('g:i A')]) }}
                            @endif
                            .
                        @endif
                    </p>
                    <p class="text-body mb-0">{{ __('Party size :count', ['count' => $guest->party_size]) }}, {{ $guest->rsvp_status->label() }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
