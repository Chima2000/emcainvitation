@extends('layouts.dashboard')

@section('title', __('Review :purpose', ['purpose' => mb_strtolower($purpose->label())]))
@section('heading', __('Review before preparing'))
@section('subheading')
    <a href="{{ route('events.show', $event) }}#{{ $purpose === \App\MessagePurpose::Pledge ? 'pledges' : 'messages' }}">{{ __('Back to :title', ['title' => $event->title]) }}</a>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                    @endif
                    <p class="text-body">{{ __('This is one real guest. Nothing is prepared and nothing is taken from the wallet until you confirm.') }}</p>
                    <ul class="list-unstyled">
                        <li>{{ __('Messages') }}: {{ number_format($quote['count']) }}</li>
                        <li>{{ __('Covered by the package') }}: {{ number_format($quote['covered']) }}</li>
                        <li>{{ __('From the wallet') }}: TZS {{ number_format($quote['charged']) }}</li>
                        <li>{{ __('Wallet balance') }}: TZS {{ number_format($quote['balance']) }}</li>
                        @if ($quote['unreachable'] > 0)
                            <li>{{ trans_choice(':count guest has no phone number and would be skipped.|:count guests have no phone number and would be skipped.', $quote['unreachable'], ['count' => $quote['unreachable']]) }}</li>
                        @endif
                    </ul>
                    @if ($quote['guest'])
                        <div class="border rounded p-3 mb-3">
                            <p class="fw-semibold mb-1">{{ $quote['guest']->name }}@if ($quote['guest']->table_name) · {{ $quote['guest']->table_name }}@endif</p>
                            <p class="mb-0">{{ $quote['body'] }}</p>
                        </div>
                    @else
                        <div class="alert alert-info">{{ __('Nobody is waiting for this :purpose.', ['purpose' => mb_strtolower($purpose->label())]) }}</div>
                    @endif
                    @if ($quote['count'] > 0 && $quote['shortfall'] === 0)
                        <form method="POST" action="{{ route(match ($purpose) {
                            \App\MessagePurpose::Reminder => 'events.reminders.send',
                            \App\MessagePurpose::Thanks => 'events.thanks.send',
                            \App\MessagePurpose::Pledge => 'events.pledges.notify',
                            default => 'events.invitations.send',
                        }, $event) }}">
                            @csrf
                            <input type="hidden" name="channel" value="{{ $channel->value }}">
                            <input type="hidden" name="audience" value="{{ $audience }}">
                            <input type="hidden" name="group_id" value="{{ $groupId }}">
                            <input type="hidden" name="reviewed" value="1">
                            <x-button>{{ __('Prepare these messages') }}</x-button>
                        </form>
                    @elseif ($quote['shortfall'] > 0)
                        <div class="alert alert-warning" role="alert">
                            {{ __('This batch needs TZS :amount more in the wallet before it can start.', ['amount' => number_format($quote['shortfall'])]) }}
                            <a href="{{ route('wallet.index') }}">{{ __('Wallet') }}</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
