@extends('layouts.dashboard')

@section('title', __('Complete your profile'))
@section('heading', $profileRequired ? __('Complete your profile') : __('Profile details'))
@section('subheading', $profileRequired
    ? __('Add your location details to continue to your dashboard.')
    : __('Update your location and address details.'))

@push('styles')
    <style>
        body.profile-edit-page .app-page-head {
            margin-bottom: 1.5rem;
        }

        .profile-edit-page-content {
            max-width: 820px;
            margin-inline: auto;
        }
    </style>
@endpush

@section('content')
    <div class="profile-edit-page-content">
        <div class="row justify-content-center">
            <div class="col-12">
                @if ($profileRequired)
                    <div class="alert alert-primary" role="status">
                        <strong>{{ __('Step 2 of 2') }}</strong> · {{ __('Your account is registered. Complete your location details to continue.') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('profile.update') }}" data-multistep data-location-form data-location-url="{{ asset('data/tanzania-locations.json') }}" data-start-step="{{ $errors->hasAny(['street', 'address_line']) ? 2 : 1 }}">
                            @csrf
                            @method('PUT')

                            <div class="d-flex gap-2 mb-4" aria-label="{{ __('Profile steps') }}">
                                <span class="badge bg-primary" data-step-indicator="1">{{ __('1. Location') }}</span>
                                <span class="badge bg-light text-dark border" data-step-indicator="2">{{ __('2. Address') }}</span>
                            </div>

                            <section data-step-panel="1">
                                <h2 class="h6 mb-3">{{ __('Choose your location') }}</h2>
                                <x-profile-location-fields :user="$user" :required-fields="true" id-prefix="profile-" />
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="button" class="btn btn-primary" data-step-next>{{ __('Continue') }} <i class="fi fi-rr-arrow-small-right ms-1"></i></button>
                                </div>
                            </section>

                            <section data-step-panel="2" hidden>
                                <h2 class="h6 mb-3">{{ __('Add your street address') }}</h2>
                                <div class="mb-3">
                                    <label class="form-label" for="profile-street">{{ __('Street / road') }}</label>
                                    <input class="form-control @error('street') is-invalid @enderror" id="profile-street" type="text" name="street" value="{{ old('street', $user->street) }}" autocomplete="street-address" maxlength="180" data-step-required>
                                    @error('street')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="profile-address-line">{{ __('House, building or landmark (optional)') }}</label>
                                    <input class="form-control @error('address_line') is-invalid @enderror" id="profile-address-line" type="text" name="address_line" value="{{ old('address_line', $user->address_line) }}" maxlength="255">
                                    @error('address_line')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-flex justify-content-between gap-2 mt-4">
                                    <button type="button" class="btn btn-light" data-step-back>{{ __('Back') }}</button>
                                    <x-button>{{ __('Save and continue') }}</x-button>
                                </div>
                            </section>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
