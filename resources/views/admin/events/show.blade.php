@extends('layouts.admin')

@section('title', $event->title)
@section('heading', $event->title)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">{{ __('Events') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $event->title }}</li>
@endsection

@section('actions')
    @if ($event->user)
        <a href="{{ route('admin.users.show', $event->user) }}" class="btn btn-outline-light btn-shadow waves-effect">
            <i class="fi fi-rr-user me-1"></i> {{ $event->user->name }}
        </a>
    @endif
@endsection

@section('content')
    @php
        $responseRate = $event->guests_count > 0 ? (int) round((($event->attending_count + $event->declined_count) / $event->guests_count) * 100) : 0;
    @endphp

    <div class="row">
        @foreach ([
            ['label' => __('Guests'), 'value' => number_format($event->guests_count), 'icon' => 'fi-rr-users-alt', 'tone' => ''],
            ['label' => __('Attending'), 'value' => number_format($event->attending_count), 'icon' => 'fi-rr-badge-check', 'tone' => 'is-soft'],
            ['label' => __('Checked in'), 'value' => number_format($event->checked_in_count), 'icon' => 'fi-rr-calendar-check', 'tone' => 'is-dark'],
            ['label' => __('Messages'), 'value' => number_format($event->messages_count), 'icon' => 'fi-rr-paper-plane', 'tone' => 'is-soft'],
        ] as $kpi)
            <div class="col-xl-3 col-sm-6">
                <div class="card emca-kpi">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="emca-kpi-icon {{ $kpi['tone'] }}"><i class="fi {{ $kpi['icon'] }}"></i></span>
                        <div>
                            <span class="d-block text-body text-sm">{{ $kpi['label'] }}</span>
                            <strong class="d-block fs-5 text-dark">{{ $kpi['value'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">{{ __('Event details') }}</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 gy-2">
                        <dt class="col-5 text-body fw-normal">{{ __('Type') }}</dt>
                        <dd class="col-7 mb-0">{{ $event->type->label() }}</dd>
                        <dt class="col-5 text-body fw-normal">{{ __('Hosts') }}</dt>
                        <dd class="col-7 mb-0">{{ $event->hostsLabel() }}</dd>
                        <dt class="col-5 text-body fw-normal">{{ __('Date') }}</dt>
                        <dd class="col-7 mb-0">{{ $event->event_date->translatedFormat('d M Y, H:i') }}</dd>
                        <dt class="col-5 text-body fw-normal">{{ __('Venue') }}</dt>
                        <dd class="col-7 mb-0">{{ $event->venue }}</dd>
                        <dt class="col-5 text-body fw-normal">{{ __('Status') }}</dt>
                        <dd class="col-7 mb-0"><span class="badge bg-light text-dark">{{ $event->status->label() }}</span></dd>
                        <dt class="col-5 text-body fw-normal">{{ __('Card design') }}</dt>
                        <dd class="col-7 mb-0">{{ $event->template?->name ?? __('Default design') }}</dd>
                        <dt class="col-5 text-body fw-normal">{{ __('Replies') }}</dt>
                        <dd class="col-7 mb-0">
                            <div class="progress" style="height: 6px;" role="progressbar" aria-valuenow="{{ $responseRate }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-primary" style="width: {{ $responseRate }}%"></div>
                            </div>
                            <small class="text-body">{{ __(':percent% replied, :pending waiting', ['percent' => $responseRate, 'pending' => $event->pending_count]) }}</small>
                        </dd>
                        <dt class="col-5 text-body fw-normal">{{ __('Gifts received') }}</dt>
                        <dd class="col-7 mb-0">TZS {{ number_format($pledged) }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Billing') }}</h6>
                    <span class="fw-bold">TZS {{ number_format($spent) }}</span>
                </div>
                <div class="card-body">
                    @if ($event->hasPackage())
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="emca-kpi-icon"><i class="fi fi-rr-box-open"></i></span>
                            <div>
                                <strong class="d-block">{{ $event->package?->name ?? __('Package') }}</strong>
                                <small class="text-body">{{ __(':guests guests · bought :date', ['guests' => number_format($event->package_guest_limit), 'date' => $event->package_purchased_at->translatedFormat('d M Y')]) }}</small>
                            </div>
                        </div>
                        <ul class="list-unstyled d-grid gap-1 mb-0 text-sm">
                            @foreach (\App\MessagePurpose::cases() as $purposeCase)
                                <li class="d-flex justify-content-between"><span>{{ $purposeCase->label() }}</span><span class="text-body">{{ __(':count left', ['count' => number_format($event->packageAllowanceLeft($purposeCase))]) }}</span></li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-body mb-0">{{ __('Pay as you go. Each message is charged to the host wallet.') }}</p>
                    @endif
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($charges as $charge)
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <span class="min-w-0">
                                <span class="d-block text-truncate">{{ $charge->description }}</span>
                                <small class="text-body">{{ $charge->created_at->translatedFormat('d M, H:i') }}</small>
                            </span>
                            <span @class(['text-nowrap fw-semibold', 'emca-money-in' => $charge->isCredit(), 'emca-money-out' => ! $charge->isCredit()])>{{ $charge->isCredit() ? '+' : '−' }}{{ number_format(abs($charge->amount)) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">{{ __('Guest list') }}</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Guest') }}</th>
                                <th>{{ __('Pass') }}</th>
                                <th class="text-center">{{ __('Party') }}</th>
                                <th>{{ __('Reply') }}</th>
                                <th>{{ __('Checked in') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($guests as $guest)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $guest->name }}</span>
                                        <small class="d-block text-body">{{ $guest->phone }}</small>
                                    </td>
                                    <td><code class="text-dark">{{ $guest->serial }}</code></td>
                                    <td class="text-center">{{ $guest->party_size }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $guest->rsvp_status->label() }}</span></td>
                                    <td class="text-nowrap">{{ $guest->checked_in_at?->translatedFormat('d M, H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="emca-empty">{{ __('No guests added yet.') }}</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mb-4">{{ $guests->links('pagination::bootstrap-5') }}</div>

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Latest messages') }}</h6>
                    <a href="{{ route('admin.messages.index') }}" class="btn btn-sm btn-outline-light btn-shadow">{{ __('All messages') }}</a>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($messages as $message)
                        <li class="list-group-item d-flex gap-3 align-items-start">
                            <span class="emca-kpi-icon is-soft flex-shrink-0"><i class="fi fi-rr-paper-plane"></i></span>
                            <div class="min-w-0 me-auto">
                                <span class="d-block fw-semibold">{{ $message->guest?->name }} · {{ $message->purpose->label() }}</span>
                                <small class="d-block text-body text-truncate">{{ \Illuminate\Support\Str::limit($message->body, 110) }}</small>
                            </div>
                            <span class="text-nowrap text-sm">
                                {{ $message->channel->label() }}
                                <small class="d-block text-body text-end">{{ $message->covered_by_package ? __('Package') : 'TZS '.number_format($message->cost) }}</small>
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item"><div class="emca-empty py-3">{{ __('No messages yet.') }}</div></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
