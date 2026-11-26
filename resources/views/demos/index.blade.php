@extends('layouts.admin')

@section('title', __('Demo requests'))
@section('heading', __('Demo requests'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Customers') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Demo requests') }}</li>
@endsection

@section('content')
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('When') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Contact') }}</th>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Note') }}</th>
                        <th>{{ __('Trial') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($demos as $demo)
                        <tr>
                            <td class="text-nowrap">
                                {{ $demo->created_at->timezone(config('app.timezone'))->translatedFormat('j M Y, g:i A') }}
                                <small class="d-block text-body">{{ $demo->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <p class="fw-semibold mb-0">{{ $demo->name }}</p>
                                <p class="text-body mb-0">{{ $demo->organization }}</p>
                            </td>
                            <td>
                                <a href="tel:{{ $demo->phone }}" class="d-block text-dark">{{ $demo->phone }}</a>
                                <a href="mailto:{{ $demo->email }}" class="d-block text-body">{{ $demo->email }}</a>
                            </td>
                            <td>
                                {{ $demo->event_type?->label() ?? __('None') }}
                                @if ($demo->guest_count)
                                    <span class="text-body">, {{ trans_choice(':count guest|:count guests', $demo->guest_count) }}</span>
                                @endif
                            </td>
                            <td class="text-body">{{ $demo->message }}</td>
                            <td style="min-width: 16rem;">
                                @php($trialErrors = $errors->getBag('trial-'.$demo->id))
                                <form method="POST" action="{{ route('demos.trial', $demo) }}" class="d-flex flex-wrap align-items-start gap-2">
                                    @csrf
                                    <div>
                                        <label class="form-label mb-1" for="trial-days-{{ $demo->id }}">{{ __('Days') }}</label>
                                        <input
                                            type="number"
                                            class="form-control @if ($trialErrors->has('days')) is-invalid @endif"
                                            id="trial-days-{{ $demo->id }}"
                                            name="days"
                                            min="1"
                                            max="365"
                                            value="{{ $trialErrors->any() ? old('days') : ($demo->trial_days ?? 7) }}"
                                            required
                                        >
                                    </div>
                                    <div class="pt-4 d-flex flex-wrap gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm waves-effect waves-light">
                                            {{ $demo->trialIsActive() ? __('Extend trial') : __('Allow trial') }}
                                        </button>
                                    </div>
                                </form>
                                @if ($demo->trialIsActive())
                                    <form method="POST" action="{{ route('demos.trial.cancel', $demo) }}" class="mt-2" data-emca-confirm="{{ __('Cancel the trial for :name?', ['name' => $demo->name]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger waves-effect waves-light">{{ __('Cancel trial') }}</button>
                                    </form>
                                @endif
                                @foreach ($trialErrors->all() as $message)
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @endforeach
                                @if ($demo->trial_cancelled_at)
                                    <p class="small text-body mb-1 mt-2">{{ __('Trial cancelled') }}</p>
                                @elseif ($demo->trial_ends_at)
                                    <p class="small text-body mb-1 mt-2">
                                        {{ __('Trial until :date', ['date' => $demo->trial_ends_at->timezone(config('app.timezone'))->translatedFormat('j M Y, g:i A')]) }}
                                    </p>
                                @endif
                                <form method="POST" action="{{ route('demos.destroy', $demo) }}" class="mt-2" data-emca-confirm="{{ __('Remove the demo request from :name? A trial account that was already opened stays.', ['name' => $demo->name]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger waves-effect waves-light">{{ __('Remove request') }}</button>
                                </form>
                                @if ($demo->trialIsActive())
                                    <label class="form-label mb-1" for="trial-link-{{ $demo->id }}">{{ __('Trial link') }}</label>
                                    <input class="form-control form-control-sm emca-share-link" id="trial-link-{{ $demo->id }}" value="{{ URL::temporarySignedRoute('trials.show', $demo->trial_ends_at, $demo) }}" readonly>
                                    <form method="POST" action="{{ route('demos.trial.whatsapp', $demo) }}" class="mt-2" target="_blank">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light waves-effect waves-light">
                                            <i class="fi fi-brands-whatsapp me-1"></i>{{ __('Send on WhatsApp') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"><div class="emca-empty">{{ __('No demo requests yet.') }}</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $demos->links('pagination::bootstrap-5') }}</div>
@endsection
