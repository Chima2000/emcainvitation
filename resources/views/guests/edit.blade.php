@extends('layouts.dashboard')

@section('title', __('Edit :title', ['title' => $guest->name]))
@section('heading', __('Edit guest'))
@section('subheading')
    <a href="{{ route('events.show', $event) }}">{{ __('Back to :title', ['title' => $event->title]) }}</a>
    · {{ __('Card :serial', ['serial' => $guest->serial]) }}
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('events.guests.update', [$event, $guest]) }}">
                        @csrf
                        @method('PUT')
                        <x-field name="name" label="{{ __('Name') }}">
                            <x-input name="name" value="{{ old('name', $guest->name) }}" required />
                        </x-field>
                        <div class="row">
                            <div class="col-md-6">
                                <x-field name="phone" label="{{ __('Phone') }}">
                                    <x-input name="phone" value="{{ old('phone', $guest->phone) }}" />
                                </x-field>
                            </div>
                            <div class="col-md-6">
                                <x-field name="email" label="{{ __('Email') }}">
                                    <x-input name="email" type="email" value="{{ old('email', $guest->email) }}" />
                                </x-field>
                            </div>
                        </div>
                        <x-field name="gender" label="{{ __('Gender') }}">
                            <select id="guest-gender" name="gender" class="form-select">
                                <option value="">{{ __('Prefer not to say') }}</option>
                                @foreach (\App\GuestGender::cases() as $gender)
                                    <option value="{{ $gender->value }}" @selected(old('gender', $guest->gender?->value) === $gender->value)>{{ $gender->label() }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <div class="row">
                            <div class="col-md-6">
                                <x-field name="party_size" label="{{ __('Party size') }}">
                                    <x-input name="party_size" type="number" min="1" max="30" value="{{ old('party_size', $guest->party_size) }}" required />
                                </x-field>
                            </div>
                            <div class="col-md-6">
                                <x-field name="rsvp_status" label="{{ __('Reply') }}">
                                    <select id="rsvp_status" name="rsvp_status" class="form-select">
                                        @foreach ($statuses as $status)
                                            <option value="{{ $status->value }}" @selected(old('rsvp_status', $guest->rsvp_status->value) === $status->value)>{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                            </div>
                        </div>
                        <x-field name="notes" label="{{ __('Notes') }}">
                            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes', $guest->notes) }}</textarea>
                        </x-field>
                        <x-button>{{ __('Save guest') }}</x-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
