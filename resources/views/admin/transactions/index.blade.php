@extends('layouts.admin')

@section('title', __('Transactions'))
@section('heading', __('Transactions'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Finance') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Transactions') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card emca-hero-card">
                <div class="card-body">
                    <span class="emca-card-label">{{ __('All-time revenue') }}</span>
                    <strong class="d-block fs-3 fw-bold">TZS {{ number_format($totals['revenue']) }}</strong>
                    <small class="emca-card-note">{{ __('Packages, messages and subscriptions') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card emca-kpi">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="emca-kpi-icon is-soft"><i class="fi fi-rr-arrow-down-left"></i></span>
                    <div>
                        <span class="d-block text-body text-sm">{{ __('Money in (this view)') }}</span>
                        <strong class="d-block fs-5 emca-money-in">TZS {{ number_format($totals['in']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card emca-kpi">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="emca-kpi-icon is-dark"><i class="fi fi-rr-arrow-up-right"></i></span>
                    <div>
                        <span class="d-block text-body text-sm">{{ __('Money out (this view)') }}</span>
                        <strong class="d-block fs-5 emca-money-out">TZS {{ number_format($totals['out']) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="emca-filter-pills">
                <a href="{{ route('admin.transactions.index', request()->except(['type', 'page'])) }}" @class(['active' => ! $type])>{{ __('All') }}</a>
                @foreach (\App\WalletEntryType::cases() as $typeCase)
                    <a href="{{ route('admin.transactions.index', [...request()->except('page'), 'type' => $typeCase->value]) }}" @class(['active' => $type === $typeCase])>{{ $typeCase->label() }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.transactions.index') }}" class="d-flex gap-2" role="search">
                @if ($type)
                    <input type="hidden" name="type" value="{{ $type->value }}">
                @endif
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ __('Host or description') }}" aria-label="{{ __('Search transactions') }}">
                <button class="btn btn-sm btn-primary" type="submit"><i class="fi fi-rr-search"></i></button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Host') }}</th>
                        <th>{{ __('Details') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th class="text-end">{{ __('Balance after') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="text-nowrap">{{ $transaction->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td>
                                @if ($transaction->user)
                                    <a href="{{ route('admin.users.show', $transaction->user) }}" class="fw-semibold text-dark">{{ $transaction->user->name }}</a>
                                @endif
                            </td>
                            <td>
                                {{ $transaction->description }}
                                @if ($transaction->event)
                                    <small class="d-block"><a href="{{ route('admin.events.show', $transaction->event) }}" class="text-body">{{ $transaction->event->title }}</a></small>
                                @endif
                                @if ($transaction->recorder)
                                    <small class="d-block text-body">{{ __('by :name', ['name' => $transaction->recorder->name]) }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $transaction->type->label() }}</span></td>
                            <td @class(['text-end text-nowrap fw-semibold', 'emca-money-in' => $transaction->isCredit(), 'emca-money-out' => ! $transaction->isCredit()])>
                                {{ $transaction->isCredit() ? '+' : '−' }}{{ number_format(abs($transaction->amount)) }}
                            </td>
                            <td class="text-end text-nowrap">{{ number_format($transaction->balance_after) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="emca-empty">{{ __('No transactions match this view.') }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>
@endsection
