@extends('layouts.admin')

@section('title', __('Subscriptions'))
@section('heading', __('Subscriptions'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Finance') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Subscriptions') }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.top-ups.index') }}" class="btn btn-outline-light btn-shadow waves-effect">
        <i class="fi fi-rr-wallet me-1"></i> {{ __('Top-ups') }}
    </a>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <span class="d-block text-body small">{{ __('Active hosts') }}</span>
                    <strong class="fs-3">{{ number_format($activeHosts) }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <span class="d-block text-body small">{{ __('Hosts without a subscription') }}</span>
                    <strong class="fs-3">{{ number_format($inactiveHosts) }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card emca-hero-card">
                <div class="card-body">
                    <span class="emca-card-label">{{ __('Collected this month') }}</span>
                    <strong class="d-block fs-3 fw-bold">TZS {{ number_format($collectedThisMonth) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7">
            <form method="POST" action="{{ route('admin.subscriptions.update') }}" class="card">
                @csrf
                @method('PUT')
                <div class="card-header">
                    <h6 class="card-title mb-1">{{ __('Periods and prices') }}</h6>
                    <small class="text-body">{{ __('Hosts choose one of the switched-on periods and pay its price. A period with price 0 is hidden.') }}</small>
                </div>
                <div class="card-body border-bottom">
                    <input type="hidden" name="required" value="0">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="subscription-required" name="required" value="1" @checked(old('required', $required))>
                        <label class="form-check-label fw-semibold" for="subscription-required">{{ __('Hosts must pay a subscription to use the dashboard') }}</label>
                    </div>
                    <small class="text-body">{{ __('Switch this off to let every host use the system for free. Their subscription dates are kept.') }}</small>
                </div>
                <div class="list-group list-group-flush">
                    @foreach ($plans as $plan)
                        @php($key = 'plans.'.$plan->period->value)
                        <div class="list-group-item py-3">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-5">
                                    <label class="fw-semibold d-block" for="plan-{{ $plan->period->value }}">{{ $plan->period->label() }}</label>
                                    <small class="text-body">{{ trans_choice('About :count day|About :count days', $plan->period->approximateDays(), ['count' => $plan->period->approximateDays()]) }}</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">TZS</span>
                                        <input type="text" inputmode="numeric" autocomplete="off" data-emca-money class="form-control @error($key.'.price') is-invalid @enderror" id="plan-{{ $plan->period->value }}" name="plans[{{ $plan->period->value }}][price]" value="{{ old($key.'.price', $plan->price) }}" required>
                                        @error($key.'.price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <input type="hidden" name="plans[{{ $plan->period->value }}][is_active]" value="0">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="plan-active-{{ $plan->period->value }}" name="plans[{{ $plan->period->value }}][is_active]" value="1" @checked(old($key.'.is_active', $plan->is_active))>
                                        <label class="form-check-label" for="plan-active-{{ $plan->period->value }}">{{ __('Offered') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <x-button>{{ __('Save subscription settings') }}</x-button>
                </div>
            </form>
        </div>
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">{{ __('Latest subscriptions') }}</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Host') }}</th>
                                <th>{{ __('Period') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subscriptions as $subscription)
                                <tr>
                                    <td>
                                        @if ($subscription->user)
                                            <a href="{{ route('admin.users.show', $subscription->user) }}" class="fw-semibold">{{ $subscription->user->name }}</a>
                                        @else
                                            <span class="text-body">{{ __('Deleted host') }}</span>
                                        @endif
                                        <small class="d-block text-body">{{ __('Until :date', ['date' => $subscription->ends_at->timezone(config('app.timezone'))->translatedFormat('j M Y')]) }}</small>
                                    </td>
                                    <td>{{ $subscription->period->label() }}</td>
                                    <td class="text-end text-nowrap">TZS {{ number_format($subscription->amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3"><div class="emca-empty py-3">{{ __('No subscriptions yet.') }}</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($subscriptions->hasPages())
                    <div class="card-footer">{{ $subscriptions->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
