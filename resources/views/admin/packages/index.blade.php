@extends('layouts.admin')

@section('title', __('Packages'))
@section('heading', __('Event packages'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Catalog') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Packages') }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.pricing.edit') }}" class="btn btn-outline-light btn-shadow waves-effect">
        <i class="fi fi-rr-tags me-1"></i> {{ __('Message prices') }}
    </a>
    <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#packageModal"
        data-emca-action="{{ route('admin.packages.store') }}"
        data-emca-fill="{{ json_encode(['_method' => 'POST', 'modal_title' => __('New package'), 'name' => '', 'summary' => '', 'guest_limit' => '', 'price' => '', 'channel' => 'whatsapp', 'reminder_rounds' => 0, 'includes_thanks' => false, 'includes_pledges' => false, 'is_featured' => false, 'is_active' => true, 'sort_order' => 0]) }}">
        <i class="fi fi-rr-plus me-1"></i> {{ __('New package') }}
    </button>
@endsection

@section('content')
    <div class="alert alert-light border d-flex flex-wrap gap-2 align-items-center">
        <i class="fi fi-rr-info text-primary"></i>
        <span>{{ __('Pay as you go costs TZS :amount per guest (card + WhatsApp). Packages should beat that to be worth buying.', ['amount' => number_format($payAsYouGoPerGuest)]) }}</span>
    </div>

    <div class="row">
        @forelse ($packages as $package)
            <div class="col-xxl-3 col-lg-4 col-md-6">
                <div @class(['card emca-package-card h-100', 'is-featured' => $package->is_featured, 'opacity-75' => ! $package->is_active])>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <h2 class="h5 mb-1">{{ $package->name }}</h2>
                                <div class="d-flex flex-wrap gap-1">
                                    @if ($package->is_featured)
                                        <span class="badge bg-primary">{{ __('Most popular') }}</span>
                                    @endif
                                    @unless ($package->is_active)
                                        <span class="badge bg-secondary">{{ __('Hidden') }}</span>
                                    @endunless
                                </div>
                            </div>
                            <span class="emca-kpi-icon is-soft"><i class="fi fi-rr-box-open"></i></span>
                        </div>
                        @if ($package->summary)
                            <p class="text-body text-sm">{{ $package->summary }}</p>
                        @endif
                        <div class="emca-package-price my-2">
                            <small>TZS</small> {{ number_format($package->price) }}
                        </div>
                        <p class="text-body text-sm mb-3">{{ __('About TZS :amount per guest', ['amount' => number_format($package->pricePerGuest())]) }}</p>
                        <ul class="emca-check-list mb-3">
                            @foreach ($package->inclusions() as $inclusion)
                                <li>{{ $inclusion }}</li>
                            @endforeach
                        </ul>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between text-sm border-top pt-3 mb-3">
                                <span>{{ trans_choice(':count event|:count events', $package->events_count) }}</span>
                                <strong>TZS {{ number_format((int) $package->revenue) }}</strong>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light flex-fill" data-bs-toggle="modal" data-bs-target="#packageModal"
                                    data-emca-action="{{ route('admin.packages.update', $package) }}"
                                    data-emca-fill="{{ json_encode(['_method' => 'PUT', 'modal_title' => __('Edit :name', ['name' => $package->name]), 'name' => $package->name, 'summary' => $package->summary, 'guest_limit' => $package->guest_limit, 'price' => $package->price, 'channel' => $package->channel->value, 'reminder_rounds' => $package->reminder_rounds, 'includes_thanks' => $package->includes_thanks, 'includes_pledges' => $package->includes_pledges, 'is_featured' => $package->is_featured, 'is_active' => $package->is_active, 'sort_order' => $package->sort_order]) }}">
                                    <i class="fi fi-rr-edit me-1"></i> {{ __('Edit') }}
                                </button>
                                <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" data-emca-confirm="{{ $package->events_count > 0 ? __('Events already use :name. It will be hidden from hosts instead of deleted. Continue?', ['name' => $package->name]) : __('Delete :name?', ['name' => $package->name]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger" aria-label="{{ __('Delete') }}"><i class="fi fi-rr-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body"><div class="emca-empty">{{ __('No packages yet. Create the first one.') }}</div></div></div>
            </div>
        @endforelse
    </div>
@endsection

@push('modals')
    @php
        $packageErrors = $errors->getBag('package');
        $openPackageModal = $packageErrors->any() || request()->boolean('new');
        $packageAction = \App\Support\AdminForm::restoredAction(route('admin.packages.store'));
    @endphp
    <div class="modal fade" id="packageModal" tabindex="-1" aria-labelledby="packageModalLabel" aria-hidden="true" data-emca-open="{{ $openPackageModal ? '1' : '0' }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ $packageAction }}">
                    @csrf
                    <input type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
                    <input type="hidden" name="_form_action" value="{{ $packageAction }}">
                    <input type="hidden" name="modal_title" value="{{ old('modal_title', __('New package')) }}">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="packageModalLabel" data-emca-text="modal_title">{{ old('modal_title', __('New package')) }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label" for="package-name">{{ __('Package name') }}</label>
                                <input type="text" class="form-control @if ($packageErrors->has('name')) is-invalid @endif" id="package-name" name="name" value="{{ old('name') }}" maxlength="80" required>
                                @if ($packageErrors->has('name'))<div class="invalid-feedback">{{ $packageErrors->first('name') }}</div>@endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="package-sort">{{ __('Display order') }}</label>
                                <input type="number" class="form-control" id="package-sort" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="999">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="package-summary">{{ __('Short description') }}</label>
                                <input type="text" class="form-control @if ($packageErrors->has('summary')) is-invalid @endif" id="package-summary" name="summary" value="{{ old('summary') }}" maxlength="255" placeholder="{{ __('For example: best for church weddings') }}">
                                @if ($packageErrors->has('summary'))<div class="invalid-feedback">{{ $packageErrors->first('summary') }}</div>@endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="package-guests">{{ __('Guest limit') }}</label>
                                <input type="number" class="form-control @if ($packageErrors->has('guest_limit')) is-invalid @endif" id="package-guests" name="guest_limit" value="{{ old('guest_limit') }}" min="1" required>
                                @if ($packageErrors->has('guest_limit'))<div class="invalid-feedback">{{ $packageErrors->first('guest_limit') }}</div>@endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="package-price">{{ __('Price (TZS)') }}</label>
                                <input type="text" inputmode="numeric" autocomplete="off" data-emca-money class="form-control @if ($packageErrors->has('price')) is-invalid @endif" id="package-price" name="price" value="{{ old('price') }}" required>
                                @if ($packageErrors->has('price'))<div class="invalid-feedback">{{ $packageErrors->first('price') }}</div>@endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="package-reminders">{{ __('Reminder rounds') }}</label>
                                <input type="number" class="form-control @if ($packageErrors->has('reminder_rounds')) is-invalid @endif" id="package-reminders" name="reminder_rounds" value="{{ old('reminder_rounds', 0) }}" min="0" max="10" required>
                                @if ($packageErrors->has('reminder_rounds'))<div class="invalid-feedback">{{ $packageErrors->first('reminder_rounds') }}</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="package-channel">{{ __('Delivery channel') }}</label>
                                <select class="form-select" id="package-channel" name="channel">
                                    @foreach ($channels as $channelOption)
                                        <option value="{{ $channelOption->value }}" @selected(old('channel', 'whatsapp') === $channelOption->value)>{{ $channelOption->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <span class="form-label d-block">{{ __('Includes') }}</span>
                                @foreach ([
                                    'includes_thanks' => __('Thank-you message'),
                                    'includes_pledges' => __('Pledge desk'),
                                    'is_featured' => __('Mark as most popular'),
                                    'is_active' => __('Show to hosts'),
                                ] as $flag => $flagLabel)
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="{{ $flag }}" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="package-{{ $flag }}" name="{{ $flag }}" value="1" @checked(old($flag, $flag === 'is_active'))>
                                        <label class="form-check-label" for="package-{{ $flag }}">{{ $flagLabel }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <x-button>{{ __('Save package') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
