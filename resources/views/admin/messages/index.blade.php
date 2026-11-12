@extends('layouts.admin')

@section('title', __('Messages'))
@section('heading', __('Messages sent'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Events') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Messages') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card emca-kpi">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="emca-kpi-icon"><i class="fi fi-rr-paper-plane"></i></span>
                    <div>
                        <span class="d-block text-body text-sm">{{ __('Messages') }}</span>
                        <strong class="d-block fs-5 text-dark">{{ number_format($totals['messages']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card emca-kpi">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="emca-kpi-icon is-soft"><i class="fi fi-rr-box-open"></i></span>
                    <div>
                        <span class="d-block text-body text-sm">{{ __('Covered by a package') }}</span>
                        <strong class="d-block fs-5 text-dark">{{ number_format($totals['covered']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card emca-kpi">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="emca-kpi-icon is-dark"><i class="fi fi-rr-coins"></i></span>
                    <div>
                        <span class="d-block text-body text-sm">{{ __('Charged to wallets') }}</span>
                        <strong class="d-block fs-5 text-dark">TZS {{ number_format($totals['charged']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="emca-filter-pills">
                <a href="{{ route('admin.messages.index', request()->except(['purpose', 'page'])) }}" @class(['active' => ! $purpose])>{{ __('All') }}</a>
                @foreach (\App\MessagePurpose::cases() as $purposeCase)
                    <a href="{{ route('admin.messages.index', [...request()->except('page'), 'purpose' => $purposeCase->value]) }}" @class(['active' => $purpose === $purposeCase])>{{ $purposeCase->label() }}</a>
                @endforeach
            </div>
            <div class="emca-filter-pills">
                <a href="{{ route('admin.messages.index', request()->except(['channel', 'page'])) }}" @class(['active' => ! $channel])>{{ __('Any channel') }}</a>
                @foreach (\App\MessageChannel::cases() as $channelCase)
                    <a href="{{ route('admin.messages.index', [...request()->except('page'), 'channel' => $channelCase->value]) }}" @class(['active' => $channel === $channelCase])>{{ $channelCase->label() }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Prepared') }}</th>
                        <th>{{ __('Guest') }}</th>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Purpose') }}</th>
                        <th>{{ __('Channel') }}</th>
                        <th class="text-end">{{ __('Cost') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr>
                            <td class="text-nowrap">{{ $message->created_at->translatedFormat('d M, H:i') }}</td>
                            <td>
                                {{ $message->guest?->name }}
                                <small class="d-block text-body">{{ $message->guest?->phone }}</small>
                            </td>
                            <td>
                                @if ($message->event)
                                    <a href="{{ route('admin.events.show', $message->event) }}" class="text-dark">{{ $message->event->title }}</a>
                                    <small class="d-block text-body">{{ $message->event->user?->name }}</small>
                                @endif
                            </td>
                            <td>{{ $message->purpose->label() }}</td>
                            <td>{{ $message->channel->label() }}</td>
                            <td class="text-end text-nowrap">
                                @if ($message->covered_by_package)
                                    <span class="badge bg-primary-subtle text-primary">{{ __('Package') }}</span>
                                @else
                                    TZS {{ number_format($message->cost) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="emca-empty">{{ __('No messages match this view.') }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $messages->links('pagination::bootstrap-5') }}</div>
@endsection
