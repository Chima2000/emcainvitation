@extends('layouts.admin')

@section('title', __('Search'))
@section('heading', __('Search'))

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">{{ __('Search') }}</li>
@endsection

@section('content')
    <form method="GET" action="{{ route('admin.search') }}" class="card" role="search">
        <div class="card-body d-flex gap-2">
            <input type="search" name="q" value="{{ $term }}" class="form-control" placeholder="{{ __('Search hosts, events, references') }}" aria-label="{{ __('Search') }}" autofocus minlength="2">
            <x-button><i class="fi fi-rr-search me-1"></i> {{ __('Search') }}</x-button>
        </div>
    </form>

    @if (mb_strlen($term) < 2)
        <div class="card"><div class="card-body"><div class="emca-empty">{{ __('Type at least two letters, a phone number or a payment reference.') }}</div></div></div>
    @elseif ($hosts->isEmpty() && $events->isEmpty() && $topUps->isEmpty())
        <div class="card"><div class="card-body"><div class="emca-empty">{{ __('Nothing found for “:term”.', ['term' => $term]) }}</div></div></div>
    @else
        <div class="row">
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">{{ __('Hosts') }} <span class="badge bg-light text-dark ms-1">{{ $hosts->count() }}</span></h6></div>
                    <ul class="list-group list-group-flush">
                        @forelse ($hosts as $host)
                            <li class="list-group-item">
                                <a href="{{ route('admin.users.show', $host) }}" class="fw-semibold text-dark">{{ $host->name }}</a>
                                <small class="d-block text-body">{{ $host->phone }} · {{ trans_choice(':count event|:count events', $host->events_count) }}</small>
                            </li>
                        @empty
                            <li class="list-group-item text-body">{{ __('No hosts.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">{{ __('Events') }} <span class="badge bg-light text-dark ms-1">{{ $events->count() }}</span></h6></div>
                    <ul class="list-group list-group-flush">
                        @forelse ($events as $event)
                            <li class="list-group-item">
                                <a href="{{ route('admin.events.show', $event) }}" class="fw-semibold text-dark">{{ $event->title }}</a>
                                <small class="d-block text-body">{{ $event->event_date->translatedFormat('d M Y') }} · {{ $event->user?->name }}</small>
                            </li>
                        @empty
                            <li class="list-group-item text-body">{{ __('No events.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">{{ __('Top-ups') }} <span class="badge bg-light text-dark ms-1">{{ $topUps->count() }}</span></h6></div>
                    <ul class="list-group list-group-flush">
                        @forelse ($topUps as $topUp)
                            <li class="list-group-item d-flex justify-content-between gap-2">
                                <span class="min-w-0">
                                    <a href="{{ route('admin.top-ups.index', ['q' => $topUp->reference]) }}" class="fw-semibold text-dark">{{ $topUp->reference }}</a>
                                    <small class="d-block text-body">{{ $topUp->user?->name }} · TZS {{ number_format($topUp->amount) }}</small>
                                </span>
                                <span class="badge {{ $topUp->status->badgeClass() }} align-self-start">{{ $topUp->status->label() }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-body">{{ __('No top-ups.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    @endif
@endsection
