@extends('layouts.dashboard')

@section('title', __('Events'))
@section('heading', __('Events'))
@section('subheading', __('Every gathering on this account.'))

@section('actions')
    <x-new-event variant="primary">
        <i class="fi fi-rr-plus me-1"></i> {{ __('New event') }}
    </x-new-event>
@endsection

@section('content')
    <div class="row">
        @forelse ($events as $event)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('events.show', $event) }}" class="card text-decoration-none h-100">
                    <div class="card-body">
                        <p class="text-uppercase text-2xs fw-semibold text-muted mb-2">{{ $event->type->label() }}, {{ $event->status->label() }}</p>
                        <h2 class="h4 mb-1">{{ $event->title }}</h2>
                        <p class="text-body mb-3">{{ $event->hostsLabel() }}</p>
                        <p class="mb-1">{{ $event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A') }}</p>
                        <p class="text-body mb-3">{{ $event->venue }}</p>
                        <p class="fw-semibold mb-0">{{ trans_choice(':count guest|:count guests', $event->guests_count) }}</p>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-body">{{ __('No events yet.') }}</div>
                </div>
            </div>
        @endforelse
    </div>
    <div class="mt-3">{{ $events->links('pagination::bootstrap-5') }}</div>
@endsection
