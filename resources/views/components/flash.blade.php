@php
    $walletProblem = session('wallet_paused') ?? $errors->first('wallet');
    $canTopUp = auth()->check() && ! auth()->user()->isSuperAdmin() && ! auth()->user()->isLead();
@endphp

@if (session('status'))
    <div class="alert alert-success" role="status" data-emca-dismiss>
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger" role="alert" data-emca-dismiss>
        {{ session('error') }}
    </div>
@endif

@foreach (['top_up', 'package_id'] as $flashErrorKey)
    @if ($errors->has($flashErrorKey))
        <div class="alert alert-danger" role="alert" data-emca-dismiss>
            {{ $errors->first($flashErrorKey) }}
        </div>
    @endif
@endforeach

@if ($walletProblem)
    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert" data-emca-dismiss>
        <span><i class="fi fi-rr-wallet me-1"></i> {{ $walletProblem }}</span>
        @if ($canTopUp)
            <a href="{{ route('wallet.index') }}#top-up" class="btn btn-sm btn-primary">{{ __('Top up wallet') }}</a>
        @endif
    </div>
@endif
