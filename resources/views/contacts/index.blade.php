@extends('layouts.dashboard')

@section('title', __('Contact book'))
@section('heading', __('Contact book'))
@section('subheading', __('People you can add to any event. Saving a guest also keeps them here.'))

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('Add a contact') }}</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('contacts.store') }}">
                        @csrf
                        <x-field name="name" label="{{ __('Name') }}">
                            <x-input name="name" value="{{ old('name') }}" required />
                        </x-field>
                        <x-field name="phone" label="{{ __('Phone') }}">
                            <x-input name="phone" value="{{ old('phone') }}" />
                        </x-field>
                        <x-field name="email" label="{{ __('Email') }}">
                            <x-input name="email" type="email" value="{{ old('email') }}" />
                        </x-field>
                        <x-field name="notes" label="{{ __('Notes') }}">
                            <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                        </x-field>
                        <x-button>{{ __('Save contact') }}</x-button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('Saved contacts') }}</h2>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse ($contacts as $contact)
                            <li class="list-group-item px-0 d-flex justify-content-between gap-2">
                                <span>
                                    <strong>{{ $contact->name }}</strong>
                                    <span class="text-body d-block">{{ $contact->phone }} {{ $contact->email }}</span>
                                </span>
                                <form method="POST" action="{{ route('contacts.destroy', $contact) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('Remove') }}</button>
                                </form>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-body">{{ __('No contacts yet. Add someone here, or add a guest on an event.') }}</li>
                        @endforelse
                    </ul>
                    @if ($contacts->hasPages())
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            @if ($contacts->previousPageUrl())
                                <a class="btn btn-outline-secondary btn-sm" href="{{ $contacts->previousPageUrl() }}">{{ __('Previous') }}</a>
                            @endif
                            @if ($contacts->hasMorePages())
                                <a class="btn btn-primary btn-sm" href="{{ $contacts->nextPageUrl() }}">{{ __('Next') }}</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
