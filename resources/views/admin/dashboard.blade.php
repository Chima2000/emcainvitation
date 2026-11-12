@extends('layouts.admin')

@section('title', __('Dashboard'))
@section('heading', __('Dashboard'))
@section('subheading', $period)

@section('actions')
    <a href="{{ route('admin.top-ups.index', ['status' => 'pending']) }}" class="btn btn-outline-light btn-shadow waves-effect">
        <i class="fi fi-rr-wallet me-1"></i> {{ __('Review top-ups') }}
        @if ($stats['pending_top_ups'] > 0)
            <span class="badge rounded-pill bg-primary ms-1">{{ $stats['pending_top_ups'] }}</span>
        @endif
    </a>
    <x-button type="button" data-bs-toggle="modal" data-bs-target="#onboardHostModal">
        <i class="fi fi-rr-plus me-1"></i> {{ __('Onboard a host') }}
    </x-button>
@endsection

@section('content')
    <div class="row">
        <div class="col-xxl-8">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-start justify-content-between border-0">
                            <div class="clearfix">
                                <h2 class="fw-bold mb-0"><small class="fs-6 text-body">TZS</small> {{ number_format($stats['revenue_month']) }}</h2>
                                <span>{{ __('Revenue this month') }}</span>
                            </div>
                            <span @class(['badge', 'bg-success-subtle text-success' => $trends['revenue'] >= 0, 'bg-danger-subtle text-danger' => $trends['revenue'] < 0])>
                                <i @class(['fi scale-3x', 'fi-rr-arrow-small-up' => $trends['revenue'] >= 0, 'fi-rr-arrow-small-down' => $trends['revenue'] < 0])></i>
                                {{ $trends['revenue'] > 0 ? '+' : '' }}{{ $trends['revenue'] }}%
                            </span>
                        </div>
                        <div class="card-body pt-0">
                            <div data-emca-chart="revenueSpark" data-emca-kind="spark" class="bar-hover"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card">
                        <div class="card-header d-flex align-items-start justify-content-between border-0 mb-2">
                            <div class="clearfix">
                                <h2 class="fw-bold mb-0">{{ number_format($stats['events']) }}</h2>
                                <span>{{ __('Events') }}</span>
                            </div>
                            <span @class(['badge', 'bg-success-subtle text-success' => $trends['events'] >= 0, 'bg-danger-subtle text-danger' => $trends['events'] < 0])>
                                <i @class(['fi scale-3x', 'fi-rr-arrow-small-up' => $trends['events'] >= 0, 'fi-rr-arrow-small-down' => $trends['events'] < 0])></i>
                                {{ $trends['events'] > 0 ? '+' : '' }}{{ $trends['events'] }}%
                            </span>
                        </div>
                        <div class="card-body pt-4">
                            @php($packagedPercent = $stats['events'] === 0 ? 0 : (int) round($stats['packages_sold'] / $stats['events'] * 100))
                            <div class="d-flex align-items-center justify-content-between">
                                <h6>{{ __(':count on a package', ['count' => number_format($stats['packages_sold'])]) }}</h6>
                                <small class="text-body fw-semibold mb-2">{{ $packagedPercent }}%</small>
                            </div>
                            <div class="progress bg-primary bg-opacity-10 progress-sm" role="progressbar" aria-label="{{ __('Events on a package') }}" aria-valuenow="{{ $packagedPercent }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-primary" style="width: {{ $packagedPercent }}%"></div>
                            </div>
                            <small class="d-block text-body mt-2">{{ trans_choice(':count event opened this month|:count events opened this month', $stats['events_this_month'], ['count' => number_format($stats['events_this_month'])]) }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-start justify-content-between border-0 mb-2">
                            <div class="clearfix">
                                <h2 class="fw-bold mb-0">{{ number_format($stats['hosts']) }}</h2>
                                <span>{{ __('Hosts') }}</span>
                            </div>
                            <span @class(['badge', 'bg-success-subtle text-success' => $trends['hosts'] >= 0, 'bg-danger-subtle text-danger' => $trends['hosts'] < 0])>
                                <i @class(['fi scale-3x', 'fi-rr-arrow-small-up' => $trends['hosts'] >= 0, 'fi-rr-arrow-small-down' => $trends['hosts'] < 0])></i>
                                {{ $trends['hosts'] > 0 ? '+' : '' }}{{ $trends['hosts'] }}%
                            </span>
                        </div>
                        <div class="card-body pt-2">
                            <h6>{{ __('This month') }}</h6>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="avatar-group">
                                    @forelse ($newHosts as $newHost)
                                        <div @class(['avatar avatar-sm rounded-circle border border-2 border-white text-white', 'bg-primary' => $loop->odd, 'bg-dark' => $loop->even]) title="{{ $newHost->name }}">
                                            {{ mb_strtoupper(mb_substr($newHost->name, 0, 1)) }}
                                        </div>
                                    @empty
                                        <span class="text-body small">{{ __('No new hosts yet') }}</span>
                                    @endforelse
                                    @if ($stats['hosts_this_month'] > $newHosts->count())
                                        <div class="avatar avatar-sm bg-light border border-2 border-white rounded-circle text-dark">
                                            <span class="text-2xs fw-bold">{{ $stats['hosts_this_month'] - $newHosts->count() }}+</span>
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-icon btn-primary rounded-circle waves-effect waves-light" aria-label="{{ __('Hosts') }}">
                                    <i class="fi fi-rr-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-lg-12">
            <div class="card emca-hero-card">
                <div class="card-body pb-0 pt-3">
                    <div class="row">
                        <div class="col-7 d-flex flex-column justify-content-between pb-3 pt-1">
                            <div class="clearfix">
                                <h2 class="fw-bold mb-0">TZS {{ number_format($stats['pending_top_up_amount']) }}</h2>
                                <span>{{ trans_choice(':count top-up waiting for confirmation|:count top-ups waiting for confirmation', $stats['pending_top_ups'], ['count' => $stats['pending_top_ups']]) }}</span>
                            </div>
                            <div class="clearfix mt-3">
                                <a href="{{ route('admin.top-ups.index', ['status' => 'pending']) }}" class="btn btn-sm btn-white waves-effect btn-shadow">{{ __('Review top-ups') }}</a>
                            </div>
                        </div>
                        <div class="col-5 text-end align-self-end">
                            <img src="{{ asset('admin/assets/images/media/svg/emca-media1.svg') }}" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between border-0 pb-0">
                    <div>
                        <h6 class="card-title mb-0">{{ __('Revenue summary') }}</h6>
                        <small class="text-body">{{ __('Package sales and message charges over the last six months.') }}</small>
                    </div>
                    <a href="{{ route('admin.transactions.index') }}" class="btn btn-sm btn-outline-light btn-shadow waves-effect">{{ __('All transactions') }}</a>
                </div>
                <div class="card-body p-2">
                    <div data-emca-chart="revenue" data-emca-kind="bar"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Guest replies') }}</h6>
                    <span class="badge bg-primary-subtle text-primary">{{ __(':percent% replied', ['percent' => $stats['replied_percent']]) }}</span>
                </div>
                <div class="card-body py-0 px-3 d-flex my-3 align-items-center justify-content-center">
                    <div class="maxw-250px ratio ratio-1x1">
                        <canvas data-emca-chart="replies" data-emca-kind="doughnut" data-emca-style="thin"></canvas>
                    </div>
                </div>
                <div class="card-footer pt-0 border-0 px-3">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        @foreach ($rsvp as $status)
                            <div class="d-flex gap-1 align-items-center mx-1 text-1xs">
                                <i class="fa fa-circle text-3xs" data-emca-swatch="{{ $loop->index }}"></i>
                                <strong class="text-dark fw-semibold">{{ number_format($status['total']) }}</strong> {{ $status['label'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-6">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Collections this month') }}</h6>
                    <a href="{{ route('admin.transactions.index') }}" class="btn btn-sm btn-outline-light btn-shadow waves-effect">{{ __('Statement') }}</a>
                </div>
                <div class="card-body pb-3">
                    <div class="overflow-hidden">
                        <div data-emca-chart="collections" data-emca-kind="gauge" class="emca-gauge"></div>
                    </div>
                    <div class="text-center mb-3">
                        <div class="bg-light text-dark py-1 px-2 rounded-2 text-2xs d-inline-flex mb-2">
                            <span @class(['me-1', 'text-success' => $trends['top_ups'] >= 0, 'text-danger' => $trends['top_ups'] < 0])>{{ $trends['top_ups'] > 0 ? '+' : '' }}{{ $trends['top_ups'] }}%</span>
                            {{ __('vs last month') }}
                        </div>
                        <p class="mb-0">{{ __('Hosts topped up TZS :amount this month. TZS :pending is waiting for your confirmation.', ['amount' => number_format($stats['top_ups_month']), 'pending' => number_format($stats['pending_top_up_amount'])]) }}</p>
                    </div>
                    <div class="bg-primary bg-opacity-05 py-2 px-3 rounded-3 d-flex">
                        <div class="text-center w-50 py-2">
                            <h6 class="mb-1">{{ number_format($stats['revenue_month']) }}</h6>
                            <span class="text-primary text-2xs d-block">{{ __('Revenue') }}</span>
                        </div>
                        <div class="vr"></div>
                        <div class="text-center w-50 py-2">
                            <h6 class="mb-1">{{ number_format($stats['wallet_float']) }}</h6>
                            <span class="text-primary text-2xs d-block">{{ __('In wallets') }}</span>
                        </div>
                        <div class="vr"></div>
                        <div class="text-center w-50 py-2">
                            <h6 class="mb-1">{{ number_format($stats['packages_sold']) }}</h6>
                            <span class="text-primary text-2xs d-block">{{ __('Packages') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-6">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Top packages') }}</h6>
                    <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-outline-light btn-shadow waves-effect">{{ __('View all') }}</a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @forelse ($topPackages as $topPackage)
                            <div class="col-6">
                                <div @class([
                                    'text-center p-3 rounded-3 position-relative h-100',
                                    'bg-light' => $loop->index % 4 === 0,
                                    'bg-primary-subtle' => $loop->index % 4 === 1,
                                    'bg-secondary-subtle' => $loop->index % 4 === 2,
                                    'bg-danger-subtle' => $loop->index % 4 === 3,
                                ])>
                                    <h6 class="mb-2 text-sm">
                                        <a href="{{ route('admin.packages.index') }}" class="text-dark stretched-link">{{ $topPackage->name }}</a>
                                    </h6>
                                    <div class="avatar avatar-sm rounded-circle bg-white text-primary mx-auto mb-2">
                                        <i class="fi fi-rr-box-open"></i>
                                    </div>
                                    <div class="bg-body text-dark py-1 px-2 rounded-2 text-2xs d-inline-flex">
                                        {{ trans_choice(':count sold|:count sold', $topPackage->events_count) }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-body">{{ __('No packages yet.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="card-title mb-0">{{ __('Waiting for confirmation') }}</h6>
                        <span class="badge bg-primary-subtle text-primary">{{ number_format($stats['pending_top_ups']) }}</span>
                    </div>
                    <a href="{{ route('admin.top-ups.index', ['status' => 'pending']) }}" class="btn-link">{{ __('Review top-ups') }}</a>
                </div>
                <div class="card-body pb-3 emca-scroll-card" data-simplebar>
                    <ul class="list-group list-group-hover list-group-smooth list-group-unlined list-group-outer">
                        @forelse ($pendingTopUps as $pendingTopUp)
                            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <div class="avatar avatar-sm rounded-circle bg-primary text-white flex-shrink-0">{{ mb_strtoupper(mb_substr($pendingTopUp->user?->name ?? '?', 0, 1)) }}</div>
                                <div class="me-auto min-w-0">
                                    <h6 class="mb-0 text-truncate">{{ $pendingTopUp->user?->name }}</h6>
                                    <small class="d-block text-truncate text-body">{{ $pendingTopUp->method->label() }} · {{ $pendingTopUp->reference }}</small>
                                </div>
                                <strong class="text-nowrap">{{ number_format($pendingTopUp->amount) }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-body">{{ __('Nothing waiting. Every top-up is confirmed.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card overflow-hidden">
                <div class="card-header d-flex gap-3 flex-wrap align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Recent events') }}</h6>
                    <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-outline-light btn-shadow waves-effect">{{ __('View all') }}</a>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table display table-row-rounded mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="minw-250px">{{ __('Event') }}</th>
                                    <th class="minw-150px">{{ __('Host') }}</th>
                                    <th class="minw-100px">{{ __('When') }}</th>
                                    <th class="minw-100px">{{ __('Guests') }}</th>
                                    <th class="minw-150px">{{ __('Package') }}</th>
                                    <th class="minw-100px">{{ __('Status') }}</th>
                                    <th class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($events as $recentEvent)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm rounded-3 bg-primary text-white flex-shrink-0">
                                                    {{ mb_strtoupper(mb_substr($recentEvent->title, 0, 1)) }}
                                                </div>
                                                <div class="ms-2 min-w-0">
                                                    <h6 class="mb-0 text-truncate">
                                                        <a href="{{ route('admin.events.show', $recentEvent) }}" class="text-dark">{{ $recentEvent->title }}</a>
                                                    </h6>
                                                    <small class="text-body">{{ $recentEvent->venue }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $recentEvent->user?->name ?? $recentEvent->hostsLabel() }}</td>
                                        <td class="text-nowrap">{{ $recentEvent->event_date->timezone(config('app.timezone'))->translatedFormat('j M Y') }}</td>
                                        <td>{{ number_format($recentEvent->guests_count) }}</td>
                                        <td>{{ $recentEvent->package?->name ?? __('Pay as you go') }}</td>
                                        <td>
                                            <span @class([
                                                'badge badge-lg',
                                                'text-bg-primary' => $recentEvent->status === \App\EventStatus::Published,
                                                'bg-light text-dark border' => $recentEvent->status === \App\EventStatus::Draft,
                                                'text-bg-dark' => $recentEvent->status === \App\EventStatus::Completed,
                                            ])>{{ $recentEvent->status->label() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.events.show', $recentEvent) }}" class="btn btn-white btn-sm btn-shadow btn-icon waves-effect" aria-label="{{ __('View') }}">
                                                <i class="fi fi-rr-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="emca-empty"><i class="fi fi-rr-calendar"></i>{{ __('No events yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <div>
                        <h6 class="card-title mb-0">{{ __('Platform activity') }}</h6>
                        <small class="text-body">{{ __('Over the last six months.') }}</small>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-light btn-shadow waves-effect">{{ __('Hosts') }}</a>
                </div>
                <div class="card-body p-2">
                    <div data-emca-chart="activity" data-emca-kind="bar"></div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Recent hosts') }}</h6>
                    <a href="{{ route('admin.users.index') }}" class="btn-link">{{ __('View all') }}</a>
                </div>
                <div class="card-body pb-3 emca-scroll-card" data-simplebar>
                    <ul class="list-group list-group-hover list-group-smooth list-group-unlined list-group-outer">
                        @forelse ($hosts as $host)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div @class(['avatar rounded-circle text-white me-1 flex-shrink-0', 'bg-primary' => $loop->odd, 'bg-dark' => $loop->even])>
                                    {{ mb_strtoupper(mb_substr($host->name, 0, 1)) }}
                                </div>
                                <div class="ms-2 me-auto min-w-0">
                                    <h6 class="mb-0 text-truncate">
                                        <a href="{{ route('admin.users.show', $host) }}" class="text-dark">{{ $host->name }}</a>
                                    </h6>
                                    <small class="d-block">{{ $host->localPhone() }} · TZS {{ number_format($host->wallet_balance) }}</small>
                                </div>
                                <span class="badge bg-primary-subtle text-primary ms-2">{{ trans_choice(':count event|:count events', $host->events_count) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-body">{{ __('No hosts yet. Onboard the first account.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-6">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Event types') }}</h6>
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
                            <span class="text-body">{{ __('No events yet.') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-4 col-md-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Attendance rate') }}</h6>
                    <span class="badge bg-light text-dark border">{{ number_format($stats['upcoming_events']) }} {{ __('Upcoming') }}</span>
                </div>
                <div class="card-body pb-0">
                    <div data-emca-chart="attendance" data-emca-kind="gauge" class="emca-gauge"></div>
                    <p class="text-center mb-0">{{ __(':attending of :total guests are attending.', ['attending' => number_format($stats['attending']), 'total' => number_format($stats['guests'])]) }}</p>
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

        <div class="col-xxl-4">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Recent transactions') }}</h6>
                    <a href="{{ route('admin.transactions.index') }}" class="btn-link">{{ __('View all') }}</a>
                </div>
                <div class="card-body pb-3 emca-scroll-card" data-simplebar>
                    <ul class="list-group list-group-hover list-group-smooth list-group-unlined list-group-outer">
                        @forelse ($recentTransactions as $entry)
                            <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <div @class(['avatar avatar-sm rounded-circle flex-shrink-0', 'bg-success-subtle text-success' => $entry->isCredit(), 'bg-primary-subtle text-primary' => ! $entry->isCredit()])>
                                    <i @class(['fi', 'fi-rr-arrow-down-left' => $entry->isCredit(), 'fi-rr-arrow-up-right' => ! $entry->isCredit()])></i>
                                </div>
                                <div class="me-auto min-w-0">
                                    <h6 class="mb-0 text-truncate">{{ $entry->user?->name }}</h6>
                                    <small class="d-block text-truncate text-body">{{ $entry->type->label() }}</small>
                                </div>
                                <strong @class(['text-nowrap', 'emca-money-in' => $entry->isCredit(), 'emca-money-out' => ! $entry->isCredit()])>{{ $entry->isCredit() ? '+' : '−' }}{{ number_format(abs($entry->amount)) }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-body">{{ __('No wallet activity yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-xxl-6 col-md-6">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h6 class="card-title mb-0">{{ __('Upcoming events') }}</h6>
                </div>
                <div class="card-body emca-scroll-card" data-simplebar>
                    @forelse ($upcomingEvents as $upcomingEvent)
                        <a href="{{ route('admin.events.show', $upcomingEvent) }}" class="d-block p-3 bg-light bg-opacity-50 mb-2 rounded text-reset">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <h6 class="mb-0 text-truncate">{{ $upcomingEvent->title }}</h6>
                                <span class="badge bg-white text-dark fw-semibold">{{ $upcomingEvent->type->label() }}</span>
                            </div>
                            <div class="text-2xs d-flex flex-wrap gap-1 align-items-center text-body mt-1">
                                <i class="fi fi-rr-marker"></i>
                                <span>{{ $upcomingEvent->venue }}</span>
                                <span class="mx-1">·</span>
                                <i class="fi fi-rr-user"></i>
                                <span>{{ $upcomingEvent->user?->name ?? $upcomingEvent->hostsLabel() }}</span>
                            </div>
                            <div class="d-flex justify-content-end mt-2">
                                <span class="text-primary text-2xs fw-semibold d-flex align-items-center">
                                    <i class="fi fi-rr-clock-three me-1"></i>
                                    {{ $upcomingEvent->event_date->timezone(config('app.timezone'))->translatedFormat('j M Y, H:i') }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <p class="text-body mb-0">{{ __('No upcoming events.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xxl-6 col-md-6">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="card-title mb-0">{{ __('Demo requests') }}</h6>
                        <span class="badge bg-primary-subtle text-primary">{{ number_format($stats['demos']) }}</span>
                    </div>
                    <a href="{{ route('demos.index') }}" class="btn-link">{{ __('View all') }}</a>
                </div>
                <div class="card-body pb-3 emca-scroll-card" data-simplebar>
                    <ul class="list-group list-group-hover list-group-smooth list-group-unlined list-group-outer">
                        @forelse ($demos as $demo)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="avatar rounded-circle bg-dark text-white me-1 flex-shrink-0">
                                    {{ mb_strtoupper(mb_substr($demo->name, 0, 1)) }}
                                </div>
                                <div class="ms-2 me-auto min-w-0">
                                    <h6 class="mb-0 text-truncate">{{ $demo->name }}</h6>
                                    <small class="d-block text-truncate">{{ $demo->organization ?: $demo->event_type?->label() }}</small>
                                </div>
                                <span class="badge bg-light text-dark border ms-2">{{ trans_choice(':count guest|:count guests', $demo->guest_count) }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-body">{{ __('No demo requests yet.') }}</li>
                        @endforelse
                    </ul>
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
