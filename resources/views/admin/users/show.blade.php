@extends('layouts.admin')

@section('title', $user->name)
@section('heading', $user->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{ __('Hosts') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
@endsection

@section('actions')
    @unless ($user->isSuperAdmin())
        <button type="button" class="btn btn-outline-light btn-shadow waves-effect" data-bs-toggle="modal" data-bs-target="#walletAdjustmentModal">
            <i class="fi fi-rr-coins me-1"></i> {{ __('Adjust wallet') }}
        </button>
    @endunless
    @can('update', $user)
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary waves-effect waves-light">
            <i class="fi fi-rr-edit me-1"></i> {{ __('Edit host') }}
        </a>
    @endcan
@endsection

@section('content')
    @php($adjustmentErrors = $errors->getBag('adjustment'))

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar avatar-xl rounded-circle bg-primary text-white mx-auto mb-3 fs-3">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                    <h2 class="h5 mb-1">{{ $user->name }}</h2>
                    <span class="badge bg-light text-dark">{{ $user->role->label() }}</span>
                    <ul class="list-unstyled text-start mt-4 mb-0 d-grid gap-3">
                        <li class="d-flex gap-2"><i class="fi fi-rr-phone-call text-primary"></i><span>{{ $user->localPhone() }} <small class="text-body">({{ $user->phone }})</small></span></li>
                        <li class="d-flex gap-2"><i class="fi fi-rr-envelope text-primary"></i><span class="text-break">{{ $user->email }}</span></li>
                        <li class="d-flex gap-2">
                            <i class="fi fi-rr-marker text-primary"></i>
                            <span>
                                @if ($user->hasCompletedProfile())
                                    {{ collect([$user->street, $user->ward, $user->district, $user->region, $user->country])->filter()->implode(', ') ?: __('Address not provided') }}
                                @else
                                    <span class="badge bg-warning-subtle text-dark">{{ __('Profile details pending') }}</span>
                                @endif
                            </span>
                        </li>
                        <li class="d-flex gap-2"><i class="fi fi-rr-calendar text-primary"></i><span>{{ __('Joined :date', ['date' => $user->created_at->translatedFormat('d M Y')]) }}</span></li>
                        @unless ($user->isSuperAdmin())
                            <li class="d-flex gap-2">
                                <i class="fi fi-rr-calendar-clock text-primary"></i>
                                <span>
                                    @if ($user->hasActiveSubscription())
                                        {{ __('Subscribed until :date', ['date' => $user->subscription_ends_at->timezone(config('app.timezone'))->translatedFormat('d M Y')]) }}
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">{{ __('No active subscription') }}</span>
                                    @endif
                                </span>
                            </li>
                            <li class="d-flex gap-2">
                                <i class="fi fi-rr-shield-check text-primary"></i>
                                <span>{{ $user->hasVerifiedPhone() ? __('Phone verified') : __('Phone not verified') }}</span>
                            </li>
                        @endunless
                    </ul>
                </div>
            </div>

            @unless ($user->isSuperAdmin())
                <div class="card emca-wallet-card">
                    <div class="card-body">
                        <span class="emca-card-label">{{ __('Wallet balance') }}</span>
                        <strong class="d-block display-6 fw-bold mb-3">TZS {{ number_format($user->wallet_balance) }}</strong>
                        <div class="emca-wallet-meta text-sm">
                            <span><small>{{ __('Topped up') }}</small><strong>TZS {{ number_format($toppedUp) }}</strong></span>
                            <span><small>{{ __('Spent') }}</small><strong>TZS {{ number_format($spent) }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h6 class="card-title mb-0">{{ __('Top-up requests') }}</h6>
                        <a href="{{ route('admin.top-ups.index', ['q' => $user->phone]) }}" class="btn btn-sm btn-outline-light btn-shadow">{{ __('View all') }}</a>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse ($topUps as $topUp)
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <div class="min-w-0 me-auto">
                                    <span class="d-block fw-semibold">TZS {{ number_format($topUp->amount) }}</span>
                                    <small class="text-body">{{ $topUp->method->label() }} · {{ $topUp->reference }}</small>
                                </div>
                                <span class="badge {{ $topUp->status->badgeClass() }}">{{ $topUp->status->label() }}</span>
                            </li>
                        @empty
                            <li class="list-group-item"><div class="emca-empty py-3">{{ __('No top-ups yet.') }}</div></li>
                        @endforelse
                    </ul>
                </div>
            @endunless
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">{{ __('Events') }} <span class="badge bg-light text-dark ms-1">{{ $user->events_count }}</span></h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Event') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-center">{{ __('Guests') }}</th>
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
                                    <td class="text-nowrap">{{ $event->event_date->translatedFormat('d M Y') }}</td>
                                    <td class="text-center">{{ $event->guests_count }}</td>
                                    <td>{{ $event->package?->name ?? __('Pay as you go') }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $event->status->label() }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="emca-empty">{{ __('This host has not created an event yet.') }}</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @unless ($user->isSuperAdmin())
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">{{ __('Wallet statement') }}</h6>
                    </div>
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
                                                    · {{ $transaction->event->title }}
                                                @endif
                                                @if ($transaction->recorder)
                                                    · {{ __('by :name', ['name' => $transaction->recorder->name]) }}
                                                @endif
                                            </small>
                                        </td>
                                        <td @class(['text-end text-nowrap fw-semibold', 'emca-money-in' => $transaction->isCredit(), 'emca-money-out' => ! $transaction->isCredit()])>
                                            {{ $transaction->isCredit() ? '+' : '−' }}{{ number_format(abs($transaction->amount)) }}
                                        </td>
                                        <td class="text-end text-nowrap">{{ number_format($transaction->balance_after) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><div class="emca-empty">{{ __('No wallet activity yet.') }}</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>
            @endunless
        </div>
    </div>
@endsection

@unless ($user->isSuperAdmin())
    @push('modals')
        <div class="modal fade" id="walletAdjustmentModal" tabindex="-1" aria-labelledby="walletAdjustmentModalLabel" aria-hidden="true" data-emca-open="{{ $adjustmentErrors->any() ? '1' : '0' }}">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.users.wallet.store', $user) }}">
                        @csrf
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="walletAdjustmentModalLabel">{{ __('Adjust wallet') }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-body">{{ __('Use this for refunds, goodwill credit or correcting a mistake. The host sees the reason on their statement.') }}</p>
                            <div class="mb-3">
                                <span class="form-label d-block">{{ __('Direction') }}</span>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="direction" id="adjust-credit" value="credit" @checked(old('direction', 'credit') === 'credit')>
                                    <label class="btn btn-outline-success" for="adjust-credit"><i class="fi fi-rr-arrow-down-left me-1"></i> {{ __('Add money') }}</label>
                                    <input type="radio" class="btn-check" name="direction" id="adjust-debit" value="debit" @checked(old('direction') === 'debit')>
                                    <label class="btn btn-outline-danger" for="adjust-debit"><i class="fi fi-rr-arrow-up-right me-1"></i> {{ __('Remove money') }}</label>
                                </div>
                                @if ($adjustmentErrors->has('direction'))
                                    <div class="invalid-feedback d-block">{{ $adjustmentErrors->first('direction') }}</div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="adjust-amount">{{ __('Amount (TZS)') }}</label>
                                <input type="text" inputmode="numeric" autocomplete="off" data-emca-money class="form-control @if ($adjustmentErrors->has('amount')) is-invalid @endif" id="adjust-amount" name="amount" value="{{ old('amount') }}" required>
                                @if ($adjustmentErrors->has('amount'))
                                    <div class="invalid-feedback">{{ $adjustmentErrors->first('amount') }}</div>
                                @endif
                            </div>
                            <div>
                                <label class="form-label" for="adjust-reason">{{ __('Reason') }}</label>
                                <input type="text" maxlength="200" class="form-control @if ($adjustmentErrors->has('reason') || $errors->has('wallet')) is-invalid @endif" id="adjust-reason" name="reason" value="{{ old('reason') }}" placeholder="{{ __('For example: refund for duplicate SMS') }}" required>
                                @if ($adjustmentErrors->has('reason'))
                                    <div class="invalid-feedback">{{ $adjustmentErrors->first('reason') }}</div>
                                @endif
                            </div>
                            <p class="text-body text-sm mt-3 mb-0">{{ __('Current balance: TZS :amount', ['amount' => number_format($user->wallet_balance)]) }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <x-button>{{ __('Save adjustment') }}</x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endpush
@endunless
