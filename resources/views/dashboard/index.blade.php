@extends('layouts.dashboard')

@section('title', __('Dashboard'))
@section('heading', __('Dashboard'))
@section('subheading', $period)

@section('actions')
    <x-new-event variant="primary">
        <i class="fi fi-rr-plus me-1"></i> {{ __('New event') }}
    </x-new-event>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-6 col-xl-4">
            <x-dashboard-stat icon="fi-sr-calendar" :value="number_format($stats['events'])" :label="__('Events')" :trend="$trends['events']" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-dashboard-stat icon="fi-sr-users" tone="dark" :value="number_format($stats['guests'])" :label="__('Guests')" :trend="$trends['guests']" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-dashboard-stat icon="fi-sr-user-check" tone="soft" :value="number_format($stats['attending'])" :label="__('Attending')" :trend="$trends['attending']" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-dashboard-stat icon="fi-sr-qrcode" tone="grey" :value="number_format($stats['checked_in'])" :label="__('Checked in')" :trend="$trends['check_ins']" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-dashboard-stat icon="fi-sr-coins" tone="deep" prefix="TZS" :value="number_format($stats['pledged'])" :label="__('Pledges received')" :trend="$trends['pledges']" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-dashboard-stat icon="fi-sr-coins" tone="grey" prefix="TZS" :value="number_format($stats['budgeted_expenses'])" :label="__('Planned event costs')" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card emca-wallet-card">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="emca-card-label mb-0">{{ __('Wallet balance') }}</span>
                        <i class="fi fi-rr-wallet"></i>
                    </div>
                    <strong class="d-block emca-wallet-figure fw-bold mb-3">TZS {{ number_format(auth()->user()->wallet_balance) }}</strong>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <a href="{{ route('wallet.index') }}#top-up" class="btn btn-sm btn-white">
                            <i class="fi fi-rr-plus me-1"></i> {{ __('Top up') }}
                        </a>
                        <a href="{{ route('packages.index') }}" class="btn btn-sm btn-outline-white">{{ __('Packages') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-7 col-lg-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <div>
                        <h6 class="card-title mb-0">{{ __('Guest activity') }}</h6>
                        <small class="text-body">{{ __('Over the last six months.') }}</small>
                    </div>
                    <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-light btn-shadow waves-effect">{{ __('All events') }}</a>
                </div>
                <div class="card-body p-2">
                    <div data-emca-chart="activity" data-emca-kind="bar"></div>
                </div>
            </div>
        </div>

        <div class="col-xxl-5 col-lg-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Guest replies') }}</h6>
                    <span class="badge bg-primary-subtle text-primary">{{ __(':percent% replied', ['percent' => $stats['replied_percent']]) }}</span>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <div class="maxw-250px ratio ratio-1x1 mx-auto">
                                <canvas data-emca-chart="replies" data-emca-kind="doughnut" data-emca-style="thin"></canvas>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-grid gap-3 mt-3 mt-sm-0">
                                @foreach ($rsvp as $status)
                                    <div class="d-flex gap-1 align-items-center mx-1">
                                        <i class="fa fa-circle text-2xs me-1" data-emca-swatch="{{ $loop->index }}"></i>
                                        <strong class="text-dark fw-semibold">{{ $stats['guests'] === 0 ? 0 : round($status['total'] / $stats['guests'] * 100) }}%</strong>
                                        {{ $status['label'] }}
                                        <span class="ms-auto text-body">{{ number_format($status['total']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row gy-3 align-items-center">
                        <div class="col-sm-7">
                            <p class="mb-0">{{ __(':seats seats confirmed by :count attending guests.', ['seats' => number_format($stats['attending_seats']), 'count' => number_format($stats['attending'])]) }}</p>
                        </div>
                        <div class="col-sm-5 text-sm-end">
                            <a href="{{ route('events.index') }}" class="btn btn-primary waves-effect waves-light">
                                <i class="fi fi-rr-users me-1"></i> {{ __('Guest lists') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-lg-5">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Recent guests') }}</h6>
                    <a href="{{ route('events.index') }}" class="btn-link">{{ __('View all') }}</a>
                </div>
                <div class="card-body pb-3">
                    <ul class="list-group list-group-hover list-group-smooth list-group-unlined list-group-outer">
                        @forelse ($recentGuests as $guest)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div @class(['avatar rounded-circle text-white me-1', 'bg-primary' => $loop->odd, 'bg-dark' => $loop->even])>
                                    {{ mb_strtoupper(mb_substr($guest->name, 0, 1)) }}
                                </div>
                                <div class="ms-2 me-auto min-w-0">
                                    <h6 class="mb-0 text-truncate">{{ $guest->name }}</h6>
                                    <small class="d-block text-truncate">{{ $guest->event?->title }}</small>
                                </div>
                                <span @class([
                                    'badge ms-2',
                                    'bg-primary-subtle text-primary' => $guest->rsvp_status === \App\RsvpStatus::Attending,
                                    'text-bg-dark' => $guest->rsvp_status === \App\RsvpStatus::Declined,
                                    'bg-light text-dark border' => $guest->rsvp_status === \App\RsvpStatus::Pending,
                                ])>{{ $guest->rsvp_status->label() }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-body">{{ __('Guests you add will appear here.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xxl-8 col-lg-7">
            <div class="card overflow-hidden">
                <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Your events') }}</h6>
                    <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-light btn-shadow waves-effect">{{ __('All events') }}</a>
                </div>
                <div class="card-body px-3 pt-2 pb-2">
                    <div class="table-responsive">
                        <table class="table display table-row-rounded mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Event') }}</th>
                                    <th>{{ __('When') }}</th>
                                    <th>{{ __('Guests') }}</th>
                                    <th>{{ __('Budgeted cost') }}</th>
                                    <th class="text-end">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($events as $recentEvent)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm rounded-circle bg-primary text-white">
                                                    {{ mb_strtoupper(mb_substr($recentEvent->title, 0, 1)) }}
                                                </div>
                                                <div class="ms-2 min-w-0">
                                                    <h6 class="mb-0">
                                                        <a href="{{ route('events.show', $recentEvent) }}" class="text-dark">{{ $recentEvent->title }}</a>
                                                    </h6>
                                                    <small class="text-body">{{ $recentEvent->type->label() }} · {{ $recentEvent->venue }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-nowrap">{{ $recentEvent->event_date->timezone(config('app.timezone'))->translatedFormat('j M Y') }}</td>
                                        <td>{{ number_format($recentEvent->guests_count) }}</td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('events.show', $recentEvent) }}#budget">TZS {{ number_format($recentEvent->expense_total ?? 0) }}</a>
                                        </td>
                                        <td class="text-end">
                                            <span @class([
                                                'badge',
                                                'bg-primary-subtle text-primary' => $recentEvent->status === \App\EventStatus::Published,
                                                'bg-light text-dark border' => $recentEvent->status === \App\EventStatus::Draft,
                                                'text-bg-dark' => $recentEvent->status === \App\EventStatus::Completed,
                                            ])>{{ $recentEvent->status->label() }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <h6 class="mb-1">{{ __('No events yet') }}</h6>
                                            <p class="text-body mb-3">{{ __('No events yet. Create one to start a guest list.') }}</p>
                                            <x-new-event variant="primary">
                                                <i class="fi fi-rr-plus me-1"></i> {{ __('New event') }}
                                            </x-new-event>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Event types') }}</h6>
                    <a href="{{ route('events.index') }}" class="btn-link">{{ __('View all') }}</a>
                </div>
                <div class="card-body py-0 px-3 d-flex align-items-center justify-content-center">
                    <div class="maxw-250px ratio ratio-1x1">
                        <canvas data-emca-chart="types" data-emca-kind="doughnut"></canvas>
                    </div>
                </div>
                <div class="card-footer pt-0 border-0">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        @forelse ($eventTypes as $type)
                            <div class="d-flex gap-1 align-items-center mx-1">
                                <i class="fa fa-circle text-2xs" data-emca-swatch="{{ $loop->index }}"></i>
                                <strong class="text-dark fw-semibold">{{ number_format($type['total']) }}</strong> {{ $type['label'] }}
                            </div>
                        @empty
                            <span class="text-body">{{ __('Your event types will appear here once you create an event.') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Door check-in') }}</h6>
                    <span class="badge bg-light text-dark border">{{ number_format($stats['upcoming_events']) }} {{ __('Upcoming') }}</span>
                </div>
                <div class="card-body pb-0">
                    <div data-emca-chart="checkIn" data-emca-kind="gauge" class="emca-gauge"></div>
                    <p class="text-center mb-0">{{ __(':checked of :total guests are checked in.', ['checked' => number_format($stats['checked_in']), 'total' => number_format($stats['guests'])]) }}</p>
                </div>
                <div class="card-footer border-0">
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="bg-primary bg-opacity-05 rounded py-2">
                                <h6 class="mb-0">{{ number_format($stats['attending']) }}</h6>
                                <small class="text-body">{{ __('Attending') }}</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-primary bg-opacity-05 rounded py-2">
                                <h6 class="mb-0">{{ number_format($stats['pending']) }}</h6>
                                <small class="text-body">{{ \App\RsvpStatus::Pending->label() }}</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-primary bg-opacity-05 rounded py-2">
                                <h6 class="mb-0">{{ number_format($stats['declined']) }}</h6>
                                <small class="text-body">{{ \App\RsvpStatus::Declined->label() }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <div>
                        <h6 class="card-title mb-0">{{ __('Pledges received') }}</h6>
                        <small class="text-body">{{ __('Over the last six months.') }}</small>
                    </div>
                    <div class="text-end">
                        <h5 class="mb-0">TZS {{ number_format($stats['pledged']) }}</h5>
                        <small class="text-body">{{ __('TZS :amount this month', ['amount' => number_format($stats['pledged_this_month'])]) }}</small>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div data-emca-chart="pledges" data-emca-kind="area"></div>
                </div>
            </div>
        </div>
    </div>

    <script type="application/json" id="emca-dashboard-data">@json($chart)</script>
@endsection

@push('scripts')
    <script src="{{ asset('admin/assets/libs/chartjs/chart.js') }}"></script>
    <script src="{{ asset('admin/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('admin/assets/js/emca-dashboard.js') }}?v={{ filemtime(public_path('admin/assets/js/emca-dashboard.js')) }}" data-emca-rerun></script>
@endpush
