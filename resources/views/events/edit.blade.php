@extends('layouts.dashboard')

@section('title', __('Edit :title', ['title' => $event->title]))
@section('heading', __('Edit event'))
@section('subheading')
    <a href="{{ route('events.show', $event) }}">{{ __('Back to :title', ['title' => $event->title]) }}</a>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('events.update', $event) }}" data-event-form data-event-field-prefix="edit-event-">
                        @csrf
                        @method('PUT')
                        @include('events.partials.fields', ['fieldId' => 'edit-event-'])
                        <x-button>{{ __('Save details') }}</x-button>
                    </form>
                </div>
            </div>
            <form class="mt-3" method="POST" action="{{ route('events.destroy', $event) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Delete this event and its guest list?')) }})">
                @csrf
                @method('DELETE')
                <x-button variant="danger">{{ __('Delete event') }}</x-button>
            </form>
        </div>
    </div>
@endsection
