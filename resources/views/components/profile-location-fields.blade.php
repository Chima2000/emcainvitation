@props([
    'user' => null,
    'requiredFields' => false,
    'idPrefix' => 'profile-',
])

@php
    $currentCountry = old('country', $user?->country);
    $currentRegion = old('region', $user?->region);
    $currentDistrict = old('district', $user?->district);
    $currentWard = old('ward', $user?->ward);
    $regions = \App\Support\TanzaniaLocations::regions();
    $districts = \App\Support\TanzaniaLocations::districts($user?->region);
    $wards = \App\Support\TanzaniaLocations::wards($user?->region, $user?->district);
@endphp

<div class="row g-3" data-location-fields data-location-required="{{ $requiredFields ? 'true' : 'false' }}" data-location-manual-label="{{ __('Not listed (enter manually)') }}">
    <div class="col-md-6">
        <label class="form-label" for="{{ $idPrefix }}country">{{ __('Country') }}</label>
        <select class="form-select @error('country') is-invalid @enderror" id="{{ $idPrefix }}country" name="country" data-location-country data-current="{{ $currentCountry }}" @required($requiredFields)>
            <option value="">{{ __('Choose a country') }}</option>
            <option value="Tanzania">{{ __('Tanzania') }}</option>
            <option value="__manual__">{{ __('Another country') }}</option>
        </select>
        <div class="mt-2 d-none" data-location-manual-panel="country">
            <input class="form-control @error('country_manual') is-invalid @enderror" type="text" name="country_manual" value="{{ old('country_manual', $user?->country !== 'Tanzania' ? $user?->country : '') }}" data-location-manual="country" placeholder="{{ __('Enter country') }}">
            @error('country_manual')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @error('country')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $idPrefix }}region">{{ __('Region') }}</label>
        <select class="form-select @error('region') is-invalid @enderror" id="{{ $idPrefix }}region" name="region" data-location-region data-current="{{ $currentRegion }}" @required($requiredFields)>
            <option value="">{{ __('Choose a region') }}</option>
            @foreach ($regions as $regionName)
                <option value="{{ $regionName }}">{{ $regionName }}</option>
            @endforeach
            <option value="__manual__">{{ __('Not listed (enter manually)') }}</option>
        </select>
        <div class="mt-2 d-none" data-location-manual-panel="region">
            <input class="form-control @error('region_manual') is-invalid @enderror" type="text" name="region_manual" value="{{ old('region_manual', $currentRegion && ! in_array($currentRegion, $regions, true) ? $currentRegion : '') }}" data-location-manual="region" placeholder="{{ __('Enter region') }}">
            @error('region_manual')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @error('region')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $idPrefix }}district">{{ __('District') }}</label>
        <select class="form-select @error('district') is-invalid @enderror" id="{{ $idPrefix }}district" name="district" data-location-district data-current="{{ $currentDistrict }}" @required($requiredFields)>
            <option value="">{{ __('Choose a district') }}</option>
            @foreach ($districts as $districtName)
                <option value="{{ $districtName }}">{{ $districtName }}</option>
            @endforeach
            <option value="__manual__">{{ __('Not listed (enter manually)') }}</option>
        </select>
        <div class="mt-2 d-none" data-location-manual-panel="district">
            <input class="form-control @error('district_manual') is-invalid @enderror" type="text" name="district_manual" value="{{ old('district_manual', $currentDistrict && ! in_array($currentDistrict, $districts, true) ? $currentDistrict : '') }}" data-location-manual="district" placeholder="{{ __('Enter district') }}">
            @error('district_manual')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @error('district')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $idPrefix }}ward">{{ __('Ward') }}</label>
        <select class="form-select @error('ward') is-invalid @enderror" id="{{ $idPrefix }}ward" name="ward" data-location-ward data-current="{{ $currentWard }}" @required($requiredFields)>
            <option value="">{{ __('Choose a ward') }}</option>
            @foreach ($wards as $wardName)
                <option value="{{ $wardName }}">{{ $wardName }}</option>
            @endforeach
            <option value="__manual__">{{ __('Not listed (enter manually)') }}</option>
        </select>
        <div class="mt-2 d-none" data-location-manual-panel="ward">
            <input class="form-control @error('ward_manual') is-invalid @enderror" type="text" name="ward_manual" value="{{ old('ward_manual', $currentWard && ! in_array($currentWard, $wards, true) ? $currentWard : '') }}" data-location-manual="ward" placeholder="{{ __('Enter ward') }}">
            @error('ward_manual')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        @error('ward')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12">
        <p class="alert alert-warning d-none mb-0" role="alert" data-location-error>{{ __('The location list could not be loaded. Enter each location manually.') }}</p>
    </div>
</div>
