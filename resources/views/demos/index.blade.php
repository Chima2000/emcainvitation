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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5"><div class="emca-empty">{{ __('No demo requests yet.') }}</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $demos->links('pagination::bootstrap-5') }}</div>
@endsection
