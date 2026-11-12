@extends('layouts.dashboard')

@section('title', __('Packages'))
@section('heading', __('Packages'))
@section('subheading', __('One price per event. Cards, delivery and reminders included.'))

@section('actions')
    <a href="{{ route('wallet.index') }}" class="btn btn-outline-light btn-shadow waves-effect">
        <i class="fi fi-rr-wallet me-1"></i> TZS {{ number_format($balance) }}
    </a>
@endsection

@section('content')
    <div class="alert alert-light border d-flex flex-wrap gap-2 align-items-center">
        <i class="fi fi-rr-info text-primary"></i>
        <span>{{ __('No package? You can still pay as you go: TZS :amount per guest for the card and WhatsApp delivery.', ['amount' => number_format($payAsYouGoPerGuest)]) }}</span>
    </div>

    <div class="row">
        @forelse ($packages as $package)
            <div class="col-xl-4 col-md-6">
                <div @class(['card emca-package-card h-100', 'is-featured' => $package->is_featured])>
                    <div class="card-body d-flex flex-column p-4">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <h2 class="h5 mb-1">{{ $package->name }}</h2>
                            @if ($package->is_featured)
                                <span class="badge bg-primary">{{ __('Most popular') }}</span>
                            @endif
                        </div>
                        @if ($package->summary)
                            <p class="text-body text-sm mb-2">{{ $package->summary }}</p>
                        @endif
                        <div class="emca-package-price my-2"><small>TZS</small> {{ number_format($package->price) }}</div>
                        <p class="text-body text-sm">
                            {{ __('About TZS :amount per guest', ['amount' => number_format($package->pricePerGuest())]) }}
                            @if ($package->pricePerGuest() < $payAsYouGoPerGuest)
                                <span class="badge bg-success-subtle text-success ms-1">{{ __('Save :percent%', ['percent' => (int) round((1 - $package->pricePerGuest() / max(1, $payAsYouGoPerGuest)) * 100)]) }}</span>
                            @endif
                        </p>
                        <ul class="emca-check-list mb-4">
                            @foreach ($package->inclusions() as $inclusion)
                                <li>{{ $inclusion }}</li>
                            @endforeach
                        </ul>
                        <button type="button" @class(['btn w-100 mt-auto', 'btn-primary' => $package->is_featured, 'btn-dark' => ! $package->is_featured])
                            data-bs-toggle="modal" data-bs-target="#buyPackageModal"
                            data-emca-fill="{{ json_encode(['package_id' => $package->id, 'package_name' => $package->name, 'package_price' => 'TZS '.number_format($package->price)]) }}"
                            @disabled($events->isEmpty())>
                            {{ __('Choose :name', ['name' => $package->name]) }}
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body"><div class="emca-empty">{{ __('Packages are coming soon. You can pay as you go meanwhile.') }}</div></div></div>
            </div>
        @endforelse
    </div>

    @if ($events->isEmpty())
        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <span>{{ __('Create an upcoming event first, then pick the package that fits your guest list.') }}</span>
                <x-new-event variant="primary">
                    <i class="fi fi-rr-plus me-1"></i> {{ __('New event') }}
                </x-new-event>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header"><h6 class="card-title mb-0">{{ __('Your upcoming events') }}</h6></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Event') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="text-center">{{ __('Guests') }}</th>
                            <th>{{ __('Package') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td><a href="{{ route('events.show', $event) }}" class="fw-semibold text-dark">{{ $event->title }}</a></td>
                                <td class="text-nowrap">{{ $event->event_date->translatedFormat('d M Y') }}</td>
                                <td class="text-center">{{ $event->guests_count }}</td>
                                <td>
                                    @if ($event->hasPackage())
                                        <span class="badge bg-primary-subtle text-primary">{{ $event->package?->name ?? __('Package') }}</span>
                                        <small class="d-block text-body">{{ __('Up to :count guests', ['count' => number_format($event->package_guest_limit)]) }}</small>
                                    @else
                                        <span class="text-body">{{ __('Pay as you go') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@if ($events->isNotEmpty())
    @push('modals')
        <div class="modal fade" id="buyPackageModal" tabindex="-1" aria-labelledby="buyPackageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('events.package.store', $events->firstWhere('id', $selectedEventId) ?? $events->first()) }}" data-buy-package-form>
                        @csrf
                        <input type="hidden" name="package_id" value="">
                        <div class="modal-header">
                            <h2 class="modal-title h5" id="buyPackageModalLabel">{{ __('Buy package') }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex justify-content-between align-items-center bg-light rounded-3 p-3 mb-3">
                                <strong data-emca-text="package_name"></strong>
                                <strong class="text-primary" data-emca-text="package_price"></strong>
                            </div>
                            <label class="form-label" for="buy-package-event">{{ __('Which event is it for?') }}</label>
                            <select class="form-select" id="buy-package-event" data-buy-package-event>
                                @foreach ($events as $event)
                                    <option value="{{ route('events.package.store', $event) }}" @selected($event->id === $selectedEventId)>
                                        {{ $event->title }} · {{ $event->event_date->translatedFormat('d M Y') }}{{ $event->hasPackage() ? ' · '.__('has :name', ['name' => $event->package?->name ?? __('a package')]) : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-body text-sm mt-3 mb-0">
                                {{ __('The price comes from your wallet (balance TZS :amount). Upgrading an event that already has a package only charges the difference.', ['amount' => number_format($balance)]) }}
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <x-button>{{ __('Pay from wallet') }}</x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('[data-buy-package-form]');
                const select = document.querySelector('[data-buy-package-event]');

                if (form && select) {
                    const sync = () => {
                        form.action = select.value;
                    };

                    select.addEventListener('change', sync);
                    sync();
                }
            });
        </script>
    @endpush
@endif
