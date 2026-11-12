@extends('layouts.admin')

@section('title', __('Top-ups'))
@section('heading', __('Wallet top-ups'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Finance') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Top-ups') }}</li>
@endsection

@section('content')
    <div class="row">
        @foreach (\App\TopUpStatus::cases() as $statusCase)
            @php($summary = $counts[$statusCase->value] ?? ['total' => 0, 'amount' => 0])
            <div class="col-md-4">
                <a href="{{ route('admin.top-ups.index', ['status' => $statusCase->value]) }}" class="card emca-kpi text-decoration-none">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span @class(['emca-kpi-icon', 'is-dark' => $statusCase === \App\TopUpStatus::Approved, 'is-soft' => $statusCase === \App\TopUpStatus::Rejected])>
                            <i @class(['fi', 'fi-rr-time-past' => $statusCase === \App\TopUpStatus::Pending, 'fi-rr-badge-check' => $statusCase === \App\TopUpStatus::Approved, 'fi-rr-cross-circle' => $statusCase === \App\TopUpStatus::Rejected])></i>
                        </span>
                        <div class="min-w-0">
                            <span class="d-block text-body text-sm">{{ $statusCase->label() }}</span>
                            <strong class="d-block fs-5 text-dark">{{ number_format($summary['total']) }}</strong>
                            <small class="text-body">TZS {{ number_format($summary['amount']) }}</small>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="emca-filter-pills">
                <a href="{{ route('admin.top-ups.index', request()->except(['status', 'page'])) }}" @class(['active' => ! $status])>{{ __('All') }}</a>
                @foreach (\App\TopUpStatus::cases() as $statusCase)
                    <a href="{{ route('admin.top-ups.index', [...request()->except('page'), 'status' => $statusCase->value]) }}" @class(['active' => $status === $statusCase])>{{ $statusCase->label() }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.top-ups.index') }}" class="d-flex flex-wrap gap-2" role="search">
                @if ($status)
                    <input type="hidden" name="status" value="{{ $status->value }}">
                @endif
                <select name="method" class="form-select form-select-sm w-auto" aria-label="{{ __('Payment method') }}" onchange="this.form.requestSubmit()">
                    <option value="">{{ __('All methods') }}</option>
                    @foreach (\App\PaymentMethod::cases() as $methodCase)
                        <option value="{{ $methodCase->value }}" @selected($method === $methodCase)>{{ $methodCase->label() }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm w-auto" placeholder="{{ __('Reference, phone or host') }}" aria-label="{{ __('Search top-ups') }}">
                <button class="btn btn-sm btn-primary" type="submit"><i class="fi fi-rr-search"></i></button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Host') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th>{{ __('Paid with') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Sent') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topUps as $topUp)
                        <tr>
                            <td>
                                @if ($topUp->user)
                                    <a href="{{ route('admin.users.show', $topUp->user) }}" class="fw-semibold text-dark">{{ $topUp->user->name }}</a>
                                    <small class="d-block text-body">{{ __('Balance: TZS :amount', ['amount' => number_format($topUp->user->wallet_balance)]) }}</small>
                                @endif
                            </td>
                            <td class="text-end text-nowrap fw-bold">TZS {{ number_format($topUp->amount) }}</td>
                            <td>
                                <span class="d-flex align-items-center gap-2">
                                    <img src="{{ $topUp->method->logo() }}" alt="" class="emca-method-logo-sm" loading="lazy">
                                    {{ $topUp->method->label() }}
                                </span>
                                @if ($topUp->subscription_period)
                                    <span class="badge bg-primary-subtle text-primary mt-1">{{ __('Subscription: :period', ['period' => $topUp->subscription_period->label()]) }}</span>
                                @endif
                                @if ($topUp->payer_phone)
                                    <small class="d-block text-body">{{ $topUp->payer_phone }}</small>
                                @endif
                            </td>
                            <td>
                                <code class="text-dark">{{ $topUp->reference }}</code>
                                @if ($topUp->note)
                                    <small class="d-block text-body">{{ $topUp->note }}</small>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                {{ $topUp->created_at->translatedFormat('d M, H:i') }}
                                <small class="d-block text-body">{{ $topUp->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $topUp->status->badgeClass() }}">{{ $topUp->status->label() }}</span>
                                @if ($topUp->reviewer)
                                    <small class="d-block text-body">{{ __('by :name', ['name' => $topUp->reviewer->name]) }}</small>
                                @endif
                                @if ($topUp->review_note)
                                    <small class="d-block text-body">{{ $topUp->review_note }}</small>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($topUp->isPending())
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="{{ route('admin.top-ups.approve', $topUp) }}" data-emca-confirm="{{ $topUp->subscription_period
                                            ? __('Confirm you received TZS :amount with reference :reference? The host\'s subscription starts straight away.', ['amount' => number_format($topUp->amount), 'reference' => $topUp->reference])
                                            : __('Confirm you received TZS :amount with reference :reference? The money goes into the wallet straight away.', ['amount' => number_format($topUp->amount), 'reference' => $topUp->reference]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success"><i class="fi fi-rr-check me-1"></i>{{ __('Confirm') }}</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-light text-danger" data-bs-toggle="modal" data-bs-target="#rejectTopUpModal"
                                            data-emca-action="{{ route('admin.top-ups.reject', $topUp) }}"
                                            data-emca-fill="{{ json_encode(['summary' => ($topUp->user?->name ?? '').' · TZS '.number_format($topUp->amount).' · '.$topUp->reference, 'review_note' => '']) }}">
                                            {{ __('Decline') }}
                                        </button>
                                    </div>
                                @else
                                    <span class="text-body text-sm">{{ $topUp->reviewed_at?->translatedFormat('d M, H:i') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="emca-empty">{{ __('No top-ups match this view.') }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $topUps->links('pagination::bootstrap-5') }}</div>
@endsection

@push('modals')
    <div class="modal fade" id="rejectTopUpModal" tabindex="-1" aria-labelledby="rejectTopUpModalLabel" aria-hidden="true" data-emca-open="{{ $errors->getBag('review')->any() ? '1' : '0' }}">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ \App\Support\AdminForm::restoredAction() }}">
                    @csrf
                    <input type="hidden" name="_form_action" value="{{ \App\Support\AdminForm::restoredAction() }}">
                    <input type="hidden" name="summary" value="{{ old('summary') }}">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="rejectTopUpModalLabel">{{ __('Decline top-up') }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <p class="fw-semibold mb-2" data-emca-text="summary">{{ old('summary') }}</p>
                        <p class="text-body">{{ __('Tell the host why, for example the reference was not found or the amount is different.') }}</p>
                        <label class="form-label" for="review-note">{{ __('Note to host') }}</label>
                        <textarea class="form-control @if ($errors->getBag('review')->has('review_note')) is-invalid @endif" id="review-note" name="review_note" rows="3" maxlength="255" required>{{ old('review_note') }}</textarea>
                        @if ($errors->getBag('review')->has('review_note'))
                            <div class="invalid-feedback">{{ $errors->getBag('review')->first('review_note') }}</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <x-button variant="danger">{{ __('Decline top-up') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
