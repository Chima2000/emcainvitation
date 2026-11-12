@extends('layouts.dashboard')

@section('title', __('Subscription'))
@section('heading', __('Subscription'))
@section('subheading', __('Choose how long you want to use Jumuika Pass. The price depends on the period you choose.'))

@php
    $paymentErrors = $errors->getBag('subscriptionPayment');
    $isActive = $user->hasActiveSubscription();
@endphp

@section('content')
    <div class="row">
        <div class="col-12">
            @if ($isActive)
                <div class="alert alert-success d-flex gap-3 align-items-center" role="status">
                    <i class="fi fi-rr-badge-check fs-4"></i>
                    <div>
                        <strong class="d-block">{{ __('Your subscription is active') }}</strong>
                        {{ trans_choice('Active until :date, :count day left. Paying now adds time after that date.|Active until :date, :count days left. Paying now adds time after that date.', $user->subscriptionDaysLeft(), ['date' => $user->subscription_ends_at->timezone(config('app.timezone'))->translatedFormat('j F Y'), 'count' => $user->subscriptionDaysLeft()]) }}
                    </div>
                </div>
            @else
                <div class="alert alert-warning d-flex gap-3 align-items-center" role="alert">
                    <i class="fi fi-rr-lock fs-4"></i>
                    <div>
                        <strong class="d-block">{{ __('Your account is not active yet') }}</strong>
                        {{ $user->subscription_ends_at
                            ? __('Your subscription ended on :date. Subscribe again to open your events, guests and wallet.', ['date' => $user->subscription_ends_at->timezone(config('app.timezone'))->translatedFormat('j F Y')])
                            : __('Choose a subscription below to start using your dashboard.') }}
                    </div>
                </div>
            @endif

            @foreach ($pendingPayments as $pendingPayment)
                <div class="alert alert-info d-flex gap-3 align-items-center" role="status">
                    <img src="{{ $pendingPayment->method->logo() }}" alt="" class="emca-method-logo-sm">
                    <span>{{ __('We are checking your payment of TZS :amount (:reference) for :period. Your account opens as soon as it is confirmed.', ['amount' => number_format($pendingPayment->amount), 'reference' => $pendingPayment->reference, 'period' => mb_strtolower($pendingPayment->subscription_period->label())]) }}</span>
                </div>
            @endforeach

            @if ($plans->isEmpty())
                <div class="card"><div class="card-body"><div class="emca-empty">{{ __('Subscriptions are not open yet. Please contact EmCa support.') }}</div></div></div>
            @else
                <form method="POST" action="{{ route('subscription.store') }}" data-subscription-form>
                    @csrf
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title h6 mb-0">{{ __('1. Choose a period') }}</h2>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @foreach ($plans as $plan)
                                    @php($endsOn = $plan->period->endsAfter($isActive ? $user->subscription_ends_at : now()))
                                    <div class="col-sm-6 col-lg-4">
                                        <label class="d-block border rounded-3 p-3 h-100 emca-plan-option">
                                            <span class="d-flex justify-content-between align-items-start gap-2">
                                                <span class="fw-semibold">{{ $plan->period->label() }}</span>
                                                <input class="form-check-input" type="radio" name="period" value="{{ $plan->period->value }}" data-price="{{ $plan->price }}" @checked($selectedPeriod === $plan->period->value) required>
                                            </span>
                                            <span class="d-block emca-plan-price mt-2">TZS {{ number_format($plan->price) }}</span>
                                            <small class="d-block text-body">{{ __('About TZS :amount a day', ['amount' => number_format((int) round($plan->price / $plan->period->approximateDays()))]) }}</small>
                                            <small class="d-block text-body mt-1">{{ __('Until :date', ['date' => $endsOn->timezone(config('app.timezone'))->translatedFormat('j M Y')]) }}</small>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('period')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
                            @if ($paymentErrors->has('period'))<div class="invalid-feedback d-block mt-2">{{ $paymentErrors->first('period') }}</div>@endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h2 class="card-title h6 mb-0">{{ __('2a. Pay from your wallet') }}</h2>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <span class="text-body small">{{ __('Wallet balance') }}</span>
                                    <strong class="fs-4 mb-2" data-wallet-balance="{{ $user->wallet_balance }}">TZS {{ number_format($user->wallet_balance) }}</strong>
                                    <p class="text-body small" data-wallet-short hidden>{{ __('Your balance is not enough for this period. Pay with mobile money instead.') }}</p>
                                    @error('wallet')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                                    <button type="submit" class="btn btn-primary waves-effect waves-light w-100 mt-auto" formaction="{{ route('subscription.store') }}" formnovalidate data-wallet-pay>
                                        <i class="fi fi-rr-wallet me-1"></i> {{ __('Pay TZS') }} <span data-selected-price></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h2 class="card-title h6 mb-1">{{ __('2b. Pay with mobile money or bank') }}</h2>
                                    <small class="text-body">{{ __('Send exactly TZS') }} <strong data-selected-price></strong>, {{ __('then enter the transaction code. We confirm it and open your account.') }}</small>
                                </div>
                                <div class="card-body">
                                    <x-payment-method-options :methods="$methods" :selected="old('method', 'mpesa')" :error="$paymentErrors->first('method') ?: null" />
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="subscription-reference">{{ __('Transaction code') }}</label>
                                            <input type="text" class="form-control text-uppercase @if ($paymentErrors->has('reference')) is-invalid @endif" id="subscription-reference" name="reference" value="{{ old('reference') }}" maxlength="64" placeholder="{{ __('For example: QK71ABC2XY') }}" autocomplete="off">
                                            @if ($paymentErrors->has('reference'))<div class="invalid-feedback">{{ $paymentErrors->first('reference') }}</div>@endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="subscription-payer-phone">{{ __('Number you paid from (optional)') }}</label>
                                            <input type="tel" class="form-control @if ($paymentErrors->has('payer_phone')) is-invalid @endif" id="subscription-payer-phone" name="payer_phone" value="{{ old('payer_phone', $user->localPhone()) }}" maxlength="32">
                                            @if ($paymentErrors->has('payer_phone'))<div class="invalid-feedback">{{ $paymentErrors->first('payer_phone') }}</div>@endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer d-flex justify-content-end">
                                    <button type="submit" class="btn btn-dark waves-effect waves-light" formaction="{{ route('subscription.payment') }}">
                                        <i class="fi fi-rr-paper-plane me-1"></i> {{ __('Send payment for confirmation') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @endif
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('What you get') }}</h2></div>
                <div class="card-body">
                    <ul class="emca-check-list mb-0">
                        <li>{{ __('Unlimited events, guest lists and check-in at the door') }}</li>
                        <li>{{ __('Card designer with every template') }}</li>
                        <li>{{ __('Pledges, budget, tasks and vendors') }}</li>
                        <li>{{ __('Messages are still paid per guest or by package') }}</li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title h6 mb-0">{{ __('Subscription history') }}</h2></div>
                <ul class="list-group list-group-flush">
                    @forelse ($history as $subscription)
                        <li class="list-group-item d-flex justify-content-between gap-2">
                            <span>
                                <span class="d-block fw-semibold">{{ $subscription->period->label() }}</span>
                                <small class="text-body">{{ $subscription->starts_at->timezone(config('app.timezone'))->translatedFormat('j M Y') }} – {{ $subscription->ends_at->timezone(config('app.timezone'))->translatedFormat('j M Y') }}</small>
                            </span>
                            <strong class="text-nowrap">TZS {{ number_format($subscription->amount) }}</strong>
                        </li>
                    @empty
                        <li class="list-group-item"><div class="emca-empty py-3">{{ __('No subscriptions yet.') }}</div></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-subscription-form]');

            if (!form) {
                return;
            }

            const balance = Number(form.querySelector('[data-wallet-balance]')?.dataset.walletBalance ?? 0);
            const walletButton = form.querySelector('[data-wallet-pay]');
            const shortNote = form.querySelector('[data-wallet-short]');

            const sync = () => {
                const selected = form.querySelector('[name="period"]:checked');
                const price = Number(selected?.dataset.price ?? 0);

                form.querySelectorAll('[data-selected-price]').forEach((node) => {
                    node.textContent = price.toLocaleString('en-US');
                });

                const canAfford = selected !== null && balance >= price;
                walletButton.disabled = !canAfford;
                shortNote.hidden = canAfford || selected === null;
            };

            form.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
