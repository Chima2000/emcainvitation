@extends('layouts.admin')

@section('title', __('Events'))
@section('heading', __('All events'))

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">{{ __('Events') }}</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="emca-filter-pills">
                <a href="{{ route('admin.events.index', request()->except(['when', 'page'])) }}" @class(['active' => ! $when])>{{ __('All') }}</a>
                <a href="{{ route('admin.events.index', [...request()->except('page'), 'when' => 'upcoming']) }}" @class(['active' => $when === 'upcoming'])>{{ __('Upcoming') }}</a>
                <a href="{{ route('admin.events.index', [...request()->except('page'), 'when' => 'past']) }}" @class(['active' => $when === 'past'])>{{ __('Past') }}</a>
            </div>
            <form method="GET" action="{{ route('admin.events.index') }}" class="d-flex flex-wrap gap-2" role="search">
                @if ($when)
                    <input type="hidden" name="when" value="{{ $when }}">
                @endif
                <select name="status" class="form-select form-select-sm w-auto" aria-label="{{ __('Status') }}" onchange="this.form.requestSubmit()">
                    <option value="">{{ __('Any status') }}</option>
                    @foreach ($statuses as $statusCase)
                        <option value="{{ $statusCase->value }}" @selected($status === $statusCase)>{{ $statusCase->label() }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm w-auto" placeholder="{{ __('Title, venue or host') }}" aria-label="{{ __('Search events') }}">
                <button class="btn btn-sm btn-primary" type="submit"><i class="fi fi-rr-search"></i></button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Host') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="text-center">{{ __('Guests') }}</th>
                        <th class="text-center">{{ __('Checked in') }}</th>
                        <th>{{ __('Package') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>
                                <a href="{{ route('admin.events.show', $event) }}" class="fw-semibold text-dark">{{ $event->title }}</a>
                                <small class="d-block text-body">{{ $event->type->label() }} · {{ $event->venue }}</small>
                            </td>
                            <td>
                                @if ($event->user)
                                    <a href="{{ route('admin.users.show', $event->user) }}" class="text-dark">{{ $event->user->name }}</a>
                                    <small class="d-block text-body">{{ $event->user->phone }}</small>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                {{ $event->event_date->translatedFormat('d M Y') }}
                                <small class="d-block text-body">{{ $event->event_date->diffForHumans() }}</small>
                            </td>
                            <td class="text-center">{{ $event->guests_count }}</td>
                            <td class="text-center">{{ $event->checked_in_count }}</td>
                            <td>
                                @if ($event->package)
                                    <span class="badge bg-primary-subtle text-primary">{{ $event->package->name }}</span>
                                @else
                                    <span class="text-body text-sm">{{ __('Pay as you go') }}</span>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $event->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="emca-empty">{{ __('No events match this view.') }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $events->links('pagination::bootstrap-5') }}</div>
@endsection
