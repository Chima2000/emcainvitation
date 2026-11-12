@extends('layouts.admin')

@section('title', __('Edit host'))
@section('heading', __('Edit host'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">{{ __('Hosts') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" data-location-form data-location-url="{{ asset('data/tanzania-locations.json') }}">
                        @csrf
                        @method('PUT')
                        @include('admin.users.fields', ['user' => $user])
                        <hr>
                        <h2 class="h6 mb-3">{{ __('Location and address') }}</h2>
                        <x-profile-location-fields :user="$user" id-prefix="edit-host-" />
                        <div class="mt-3">
                            <label class="form-label" for="edit-host-street">{{ __('Street / road') }}</label>
                            <input class="form-control @error('street') is-invalid @enderror" id="edit-host-street" type="text" name="street" value="{{ old('street', $user->street) }}" maxlength="180">
                            @error('street')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mt-3">
                            <label class="form-label" for="edit-host-address-line">{{ __('House, building or landmark (optional)') }}</label>
                            <input class="form-control @error('address_line') is-invalid @enderror" id="edit-host-address-line" type="text" name="address_line" value="{{ old('address_line', $user->address_line) }}" maxlength="255">
                            @error('address_line')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <x-button>{{ __('Save host') }}</x-button>
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-light">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
