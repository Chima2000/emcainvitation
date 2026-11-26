@extends('layouts.door')

@section('title', __('Door'))

@section('content')
    @php
        $arrived = session('door_guest');
    @endphp
    <div class="card emca-door-checkin" data-door-card data-door-checking="{{ __('Checking the card…') }}">
        <div class="card-header d-flex align-items-center justify-content-between gap-2">
            <h2 class="card-title mb-0">{{ __('Door') }}</h2>
            <button type="button" class="btn btn-sm btn-light" data-door-refresh>
                <i class="fi fi-rr-refresh me-1"></i>{{ __('Refresh') }}
            </button>
        </div>
        <div class="card-body">
            @if (session('check_in') === 'verified')
                <div class="alert alert-success mb-3" role="status" data-door-result="verified" data-door-message="{{ session('door_message') }}">
                    {{ session('door_message') }}
                    @if (is_array($arrived))
                        <p class="mb-0 mt-2">{{ __('Party size :count', ['count' => $arrived['party_size']]) }}@if (filled($arrived['table_name'] ?? null)) · {{ __('Table') }} {{ $arrived['table_name'] }}@endif</p>
                    @endif
                </div>
            @elseif (session('check_in') === 'denied')
                <div class="alert alert-danger mb-3" role="alert" data-door-result="denied" data-door-message="{{ session('door_message') }}">{{ session('door_message') }}</div>
            @endif
            <div class="emca-door-workspace">
                <section class="emca-door-overview">
                    <p class="text-body mb-1 emca-door-event">{{ $event->title }}</p>
                    <h1 class="emca-door-title">{{ __(':name is at the door', ['name' => $staff->name]) }}</h1>
                    <p class="text-body emca-door-checkin-intro">{{ __('Enter the card number printed on the invitation, or scan its code. Only that card admits the guest, and it works once.') }}</p>
                </section>
                <div class="emca-door-scan">
                    <form method="POST" action="{{ route('doors.store', $staff->door_token) }}">
                        @csrf
                        @include('events.partials.check-in-scanner')
                    </form>
                </div>
                <section class="emca-door-arrivals mt-3">
                    @include('events.partials.record-toggle', [
                        'id' => 'door-arrivals',
                        'show' => __('Show arrivals'),
                        'hide' => __('Hide arrivals'),
                        'count' => $recentCheckIns->count(),
                        'open' => false,
                    ])
                    <div id="door-arrivals" hidden>
                        <ul class="list-group list-group-flush mt-3 mb-0" data-door-arrival-list>
                            @foreach ($recentCheckIns as $checkIn)
                                <li class="list-group-item px-0" data-door-arrival @if ($loop->index >= 5) hidden @endif>
                                    {{ $checkIn->name }},
                                    <span class="font-monospace">{{ $checkIn->serial }}</span>,
                                    {{ __('Party size :count', ['count' => $checkIn->party_size]) }}@if ($checkIn->table_name), {{ __('Table') }} {{ $checkIn->table_name }}@endif,
                                    {{ $checkIn->checked_in_at->timezone(config('app.timezone'))->translatedFormat('g:i A') }}
                                </li>
                            @endforeach
                        </ul>
                        @if ($recentCheckIns->count() > 5)
                            <div class="d-flex justify-content-end gap-2 mt-2" data-door-arrival-pager>
                                <button type="button" class="btn btn-sm btn-light" data-door-arrival-prev hidden>{{ __('Previous') }}</button>
                                <button type="button" class="btn btn-sm btn-light" data-door-arrival-next>{{ __('Next') }}</button>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
            <script>
                if (!window.emcaGuestListReady) {
                    window.emcaGuestListReady = true;

                    document.addEventListener('click', (event) => {
                        const button = event.target.closest('[data-guest-list-toggle]');

                        if (!button) {
                            return;
                        }

                        const list = document.getElementById(button.getAttribute('aria-controls'));

                        if (!list) {
                            return;
                        }

                        list.hidden = !list.hidden;
                        const open = !list.hidden;
                        button.setAttribute('aria-expanded', open ? 'true' : 'false');
                        const label = button.querySelector('[data-guest-list-label]');

                        if (label) {
                            label.textContent = open ? button.dataset.guestListHide : button.dataset.guestListShow;
                        }
                    });
                }
            </script>
        </div>
    </div>
@endsection
