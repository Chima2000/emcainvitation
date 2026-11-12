@extends('layouts.dashboard')

@section('title', __('Wallet'))
@section('heading', __('Wallet'))
@section('subheading', __('Top up once, then pay for packages and messages from your balance.'))

@section('actions')
    <a href="#top-up" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#topUpModal">
        <i class="fi fi-rr-plus me-1"></i> {{ __('Top up wallet') }}
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5 col-xxl-4">
            <div class="card emca-wallet-card">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="emca-card-label mb-0">{{ __('Available balance') }}</span>
                        <i class="fi fi-rr-wallet fs-5"></i>
                    </div>
                    <strong class="d-block emca-wallet-figure fw-bold mb-4">TZS {{ number_format($balance) }}</strong>
                    <div class="emca-wallet-meta mb-4 text-sm">
                        <span><small>{{ __('Waiting for confirmation') }}</small><strong>TZS {{ number_format($pendingAmount) }}</strong></span>
                        <span><small>{{ __('Spent this month') }}</small><strong>TZS {{ number_format($spentThisMonth) }}</strong></span>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-auto">
                        <button type="button" class="btn btn-white" data-bs-toggle="modal" data-bs-target="#topUpModal">
                            <i class="fi fi-rr-plus me-1"></i> {{ __('Top up wallet') }}
                        </button>
                        <a href="{{ route('packages.index') }}" class="btn btn-outline-white">{{ __('See packages') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-xxl-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <h6 class="card-title mb-0">{{ __('Your top-ups') }}</h6>
                    <small class="text-body">{{ __('Latest six requests') }}</small>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse ($topUps as $topUp)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <span class="min-w-0">
                                    <span class="d-block fw-semibold">TZS {{ number_format($topUp->amount) }}</span>
                                    <small class="d-flex align-items-center gap-2 text-body text-break">
                                        <img src="{{ $topUp->method->logo() }}" alt="" class="emca-method-logo-sm" loading="lazy">
                                        {{ $topUp->method->label() }} · {{ $topUp->reference }} · {{ $topUp->created_at->diffForHumans() }}
                                    </small>
                                </span>
                                <span class="badge {{ $topUp->status->badgeClass() }} flex-shrink-0">{{ $topUp->status->label() }}</span>
                            </div>
                            @if ($topUp->review_note)
                                <small class="d-block mt-1 text-body"><i class="fi fi-rr-comment-dots me-1"></i>{{ $topUp->review_note }}</small>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item">
                            <div class="emca-empty py-4">
                                <i class="fi fi-rr-wallet"></i>
                                {{ __('No top-ups yet.') }}
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">{{ __('Statement') }}</h6></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Details') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                                <th class="text-end">{{ __('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td class="text-nowrap">{{ $transaction->created_at->translatedFormat('d M Y, H:i') }}</td>
                                    <td>
                                        <span class="d-block">{{ $transaction->description }}</span>
                                        <small class="text-body">
                                            {{ $transaction->type->label() }}
                                            @if ($transaction->event)
                                                · <a href="{{ route('events.show', $transaction->event) }}" class="text-body">{{ $transaction->event->title }}</a>
                                            @endif
                                        </small>
                                    </td>
                                    <td @class(['text-end text-nowrap fw-semibold', 'emca-money-in' => $transaction->isCredit(), 'emca-money-out' => ! $transaction->isCredit()])>
                                        {{ $transaction->isCredit() ? '+' : '−' }}{{ number_format(abs($transaction->amount)) }}
                                    </td>
                                    <td class="text-end text-nowrap">{{ number_format($transaction->balance_after) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><div class="emca-empty">{{ __('Your statement is empty. Top up to get started.') }}</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($transactions->hasPages())
                    <div class="card-footer">{{ $transactions->links('pagination::bootstrap-5') }}</div>
                @endif
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <h6 class="card-title mb-0">{{ __('What things cost') }}</h6>
                    <a href="{{ route('packages.index') }}" class="btn btn-sm btn-outline-light btn-shadow">{{ __('See packages') }}</a>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($items as $item)
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span class="min-w-0">
                                <span class="d-block fw-semibold">{{ $item->label() }}</span>
                                <small class="text-body">{{ $item->description() }}</small>
                            </span>
                            <strong class="text-nowrap">TZS {{ number_format($rates[$item->value]) }}</strong>
                        </li>
                    @endforeach
                </ul>
                <div class="card-body pt-3">
                    <small class="text-body">{{ __('Messages covered by a package are free. When your balance cannot cover the next message, sending pauses until you top up.') }}</small>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="topUpModal" tabindex="-1" aria-labelledby="topUpModalLabel" aria-hidden="true" data-emca-open="{{ $errors->getBag('topUp')->any() ? '1' : '0' }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('wallet.top-ups.store') }}" id="top-up">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="topUpModalLabel">{{ __('Top up wallet') }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        @php($topUpErrors = $errors->getBag('topUp'))
                        <ol class="text-body ps-3 mb-4">
                            <li>{{ __('Pay with any method below.') }}</li>
                            <li>{{ __('Copy the transaction code from the confirmation message.') }}</li>
                            <li>{{ __('Send it here. We confirm it and your balance updates.') }}</li>
                        </ol>
                        <x-payment-method-options :methods="$methods" :selected="old('method', 'mpesa')" :error="$topUpErrors->first('method') ?: null" />
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="top-up-amount">{{ __('Amount paid (TZS)') }}</label>
                                <input type="text" inputmode="numeric" autocomplete="off" data-emca-money class="form-control @if ($topUpErrors->has('amount')) is-invalid @endif" id="top-up-amount" name="amount" value="{{ old('amount') }}" required>
                                @if ($topUpErrors->has('amount'))
                                    <div class="invalid-feedback">{{ $topUpErrors->first('amount') }}</div>
                                @else
                                    <small class="text-body">{{ __('Minimum TZS :amount', ['amount' => number_format($minimumTopUp)]) }}</small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="top-up-reference">{{ __('Transaction code') }}</label>
                                <input type="text" class="form-control text-uppercase @if ($topUpErrors->has('reference')) is-invalid @endif" id="top-up-reference" name="reference" value="{{ old('reference') }}" maxlength="64" placeholder="{{ __('For example: QK71ABC2XY') }}" autocomplete="off" required>
                                @if ($topUpErrors->has('reference'))
                                    <div class="invalid-feedback">{{ $topUpErrors->first('reference') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="top-up-phone">{{ __('Number you paid from (optional)') }}</label>
                                <input type="tel" class="form-control @if ($topUpErrors->has('payer_phone')) is-invalid @endif" id="top-up-phone" name="payer_phone" value="{{ old('payer_phone', auth()->user()->localPhone()) }}" maxlength="32">
                                @if ($topUpErrors->has('payer_phone'))
                                    <div class="invalid-feedback">{{ $topUpErrors->first('payer_phone') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="top-up-note">{{ __('Note (optional)') }}</label>
                                <input type="text" class="form-control @if ($topUpErrors->has('note')) is-invalid @endif" id="top-up-note" name="note" value="{{ old('note') }}" maxlength="255">
                                @if ($topUpErrors->has('note'))
                                    <div class="invalid-feedback">{{ $topUpErrors->first('note') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <x-button><i class="fi fi-rr-paper-plane me-1"></i> {{ __('Send for confirmation') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.location.hash === '#top-up' && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('topUpModal')).show();
            }
        });
    </script>
@endpush
