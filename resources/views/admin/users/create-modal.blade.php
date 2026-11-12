@php
    $openHostModal = request()->routeIs('admin.users.index', 'admin.dashboard') && $errors->any();
@endphp

<div class="modal fade" id="onboardHostModal" tabindex="-1" aria-labelledby="onboardHostModalLabel" aria-hidden="true" @if ($openHostModal) data-emca-open="1" @endif>
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3">
                <div>
                    <h5 class="modal-title" id="onboardHostModalLabel">{{ __('Onboard a host') }}</h5>
                    <p class="text-body mb-0 small">{{ __('Create the account first. The host can complete their location after signing in.') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form method="POST" action="{{ route('admin.users.store') }}" data-multistep data-location-form data-location-url="{{ asset('data/tanzania-locations.json') }}" data-start-step="{{ $errors->hasAny(['country', 'country_manual', 'region', 'region_manual', 'district', 'district_manual', 'ward', 'ward_manual', 'street', 'address_line']) ? 2 : 1 }}">
                @csrf
                <div class="modal-body">
                    <div class="d-flex gap-2 mb-4" aria-label="{{ __('Onboarding steps') }}">
                        <span class="badge bg-primary" data-step-indicator="1">{{ __('1. Account') }}</span>
                        <span class="badge bg-light text-dark border" data-step-indicator="2">{{ __('2. Optional location') }}</span>
                    </div>

                    <section data-step-panel="1">
                        @include('admin.users.fields', ['user' => null])
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary" data-step-next>{{ __('Continue') }} <i class="fi fi-rr-arrow-small-right ms-1"></i></button>
                        </div>
                    </section>

                    <section data-step-panel="2" hidden>
                        <p class="form-text">{{ __('This step is optional. The host will be asked to complete these details after their first sign-in.') }}</p>
                        <x-profile-location-fields id-prefix="onboard-host-" />
                        <div class="mb-3 mt-3">
                            <label class="form-label" for="onboard-host-street">{{ __('Street / road') }}</label>
                            <input class="form-control @error('street') is-invalid @enderror" id="onboard-host-street" type="text" name="street" value="{{ old('street') }}" maxlength="180">
                            @error('street')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="onboard-host-address-line">{{ __('House, building or landmark (optional)') }}</label>
                            <input class="form-control @error('address_line') is-invalid @enderror" id="onboard-host-address-line" type="text" name="address_line" value="{{ old('address_line') }}" maxlength="255">
                            @error('address_line')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-step-back hidden>{{ __('Back') }}</button>
                    <button type="button" class="btn btn-light waves-effect waves-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <x-button type="submit" data-step-submit hidden>{{ __('Create host') }}</x-button>
                </div>
            </form>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('onboardHostModal');

            if (modal?.dataset.emcaOpen === '1' && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    </script>
@endPushOnce
