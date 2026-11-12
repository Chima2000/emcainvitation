@extends('layouts.dashboard')

@section('title', $event->title)
@section('heading', $event->title)
@section('subheading', $event->type->label().', '.$event->status->label().' · '.$event->hostsLabel().', '.$event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A').' · '.$event->venue)

@section('actions')
    <x-button :href="route('events.design.edit', $event)">{{ __('Design the card') }}</x-button>
    <x-button :href="route('events.edit', $event)" variant="secondary">{{ __('Edit event') }}</x-button>
@endsection

@php
    $control = 'form-select';
    $when = $event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A');
@endphp

@section('content')
    <div class="row">
        @foreach ([
            [__('Guests'), $event->guests_count, 'bg-primary', 'fi-sr-users'],
            [__('Attending seats'), $headcount, 'bg-success', 'fi-sr-user-add'],
            [__('Checked in'), $event->checked_in_count, 'bg-warning', 'fi-sr-check-circle'],
            [__('Balance'), 'TZS '.number_format($income - $expense), 'bg-info', 'fi-sr-coins'],
        ] as [$label, $value, $tone, $icon])
            <div class="col-6 col-lg-3">
                <div class="card {{ $tone }} bg-opacity-05 shadow-none border-0">
                    <div class="card-body">
                        <div class="avatar avatar-sm {{ $tone }} rounded-circle text-white mb-3">
                            <i class="fi {{ $icon }}"></i>
                        </div>
                        <h3 class="mb-1">{{ $value }}</h3>
                        <h6 class="mb-0">{{ $label }}</h6>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <p class="text-body mb-4">{{ __('Waiting :pending, attending :attending, declined :declined. Pledges received: TZS :amount', ['pending' => $event->pending_count, 'attending' => $event->attending_count, 'declined' => $event->declined_count, 'amount' => number_format($receivedPledges)]) }}</p>

    <ul class="nav nav-pills gap-2 mb-4">
        @foreach (['guests' => __('Guests'), 'committees' => __('Committees'), 'check-in' => __('Door'), 'messages' => __('Messages'), 'pledges' => __('Pledges'), 'budget' => __('Budget'), 'tasks' => __('Tasks'), 'vendors' => __('Vendors')] as $id => $label)
            <li class="nav-item">
                <a class="nav-link" href="#{{ $id }}">{{ $label }}</a>
            </li>
        @endforeach
    </ul>

    <div class="row">
        <div class="col-lg-7">
            <x-invitation-card
                :design="$event->cardDesign()"
                :kicker="$event->cardKicker()"
                :title="$event->title"
                :hosts="$event->hostsLabel()"
                :headline="$event->cardHeadline()"
                :when="$when"
                :venue="$event->venue"
                :message="$event->cardMessage()"
                :dress-code="$event->dress_code"
                :dress-code-colors="$event->dress_code_colors ?? []"
            />
            <div class="d-flex flex-wrap gap-2 mt-3 mb-4">
                <a href="{{ route('events.design.edit', $event) }}" class="btn btn-primary waves-effect waves-light">
                    <i class="fi fi-rr-palette me-1"></i> {{ __('Design the card') }}
                </a>
            </div>
        </div>
        <div class="col-lg-5" id="check-in">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('Door') }}</h2>
                </div>
                <div class="card-body">
                    <p class="text-body">{{ __('Type a card number or scan the code on the invitation.') }}</p>
                    <form method="POST" action="{{ route('events.check-ins.store', $event) }}">
                        @csrf
                        <x-field name="code" label="{{ __('Card number or code') }}">
                            <x-input name="code" data-check-in-code value="{{ old('code') }}" required />
                        </x-field>
                        <div class="d-flex flex-wrap gap-2">
                            <x-button>{{ __('Check in') }}</x-button>
                            <x-button type="button" variant="secondary" data-scanner-start data-scanner-unsupported="{{ __('This browser cannot scan codes. Type the card number instead.') }}">{{ __('Scan') }}</x-button>
                        </div>
                        <video data-scanner class="hidden w-100 rounded bg-dark mt-3" playsinline></video>
                    </form>
                    <ul class="list-group list-group-flush mt-3">
                        @foreach ($recentCheckIns as $checkIn)
                            <li class="list-group-item px-0">{{ $checkIn->name }}, {{ $checkIn->checked_in_at->timezone(config('app.timezone'))->translatedFormat('g:i A') }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <section id="guests" class="mt-4">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
            <h2 class="mb-0">{{ __('Guests') }}</h2>
            <form method="GET" action="{{ route('events.show', $event) }}" class="d-flex gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('Name, phone, or card') }}" class="form-control">
                <x-button variant="secondary">{{ __('Search') }}</x-button>
            </form>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <form method="POST" action="{{ route('events.guests.store', $event) }}">
                            @csrf
                            <h3 class="h5">{{ __('Add a guest') }}</h3>
                            <x-field name="name" label="{{ __('Name') }}">
                                <x-input name="name" value="{{ old('name') }}" required />
                            </x-field>
                            <div class="row">
                                <div class="col-md-6">
                                    <x-field name="phone" label="{{ __('Phone') }}">
                                        <x-input name="phone" value="{{ old('phone') }}" />
                                    </x-field>
                                </div>
                                <div class="col-md-6">
                                    <x-field name="email" label="{{ __('Email') }}">
                                        <x-input name="email" type="email" value="{{ old('email') }}" />
                                    </x-field>
                                </div>
                            </div>
                            <x-field name="gender" label="{{ __('Gender') }}">
                                <select id="guest-gender" name="gender" class="form-select">
                                    <option value="">{{ __('Prefer not to say') }}</option>
                                    @foreach (\App\GuestGender::cases() as $gender)
                                        <option value="{{ $gender->value }}" @selected(old('gender') === $gender->value)>{{ $gender->label() }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                            <div class="row">
                                <div class="col-md-6">
                                    <x-field name="party_size" label="{{ __('Party size') }}">
                                        <x-input name="party_size" type="number" min="1" max="30" value="{{ old('party_size', 1) }}" required />
                                    </x-field>
                                </div>
                                <div class="col-md-6">
                                    <x-field name="rsvp_status" label="{{ __('Reply') }}">
                                        <select id="rsvp_status" name="rsvp_status" class="{{ $control }}">
                                            @foreach (\App\RsvpStatus::cases() as $status)
                                                <option value="{{ $status->value }}" @selected(old('rsvp_status', \App\RsvpStatus::Pending->value) === $status->value)>{{ $status->label() }}</option>
                                            @endforeach
                                        </select>
                                    </x-field>
                                </div>
                            </div>
                            <x-field name="notes" label="{{ __('Notes') }}">
                                <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                            </x-field>
                            <x-button>{{ __('Add guest') }}</x-button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <form method="POST" action="{{ route('events.guests.import', $event) }}" enctype="multipart/form-data">
                            @csrf
                            <h3 class="h5">{{ __('Import a CSV') }}</h3>
                            <p class="text-body">{{ __('Columns: name, phone, email, gender, party_size. A header row is optional. Gender is optional: female, male, other, prefer_not_to_say.') }}</p>
                            <x-field name="file" label="{{ __('File') }}">
                                <input id="file" name="file" type="file" accept=".csv,.txt" class="form-control" required>
                            </x-field>
                            <x-button variant="secondary">{{ __('Import guests') }}</x-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Guest') }}</th>
                            <th>{{ __('Card') }}</th>
                            <th>{{ __('Reply') }}</th>
                            <th>{{ __('Door') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($guests as $guest)
                            <tr>
                                <td>
                                    <p class="fw-semibold mb-0">{{ $guest->name }}</p>
                                    <p class="text-body mb-0">{{ $guest->phone }} {{ $guest->email }}{{ $guest->gender ? ' · '.$guest->gender->label() : '' }}</p>
                                </td>
                                <td class="font-monospace">{{ $guest->serial }}</td>
                                <td>{{ $guest->rsvp_status->label() }}, {{ $guest->party_size }}</td>
                                <td>{{ $guest->checked_in_at ? $guest->checked_in_at->timezone(config('app.timezone'))->translatedFormat('j M, g:i A') : __('Not yet') }}</td>
                                <td>
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        @if ($guest->whatsAppUrl())
                                            <a href="{{ $guest->whatsAppUrl() }}" target="_blank" rel="noopener">WhatsApp</a>
                                        @endif
                                        @if ($guest->smsUrl())
                                            <a href="{{ $guest->smsUrl() }}">SMS</a>
                                        @endif
                                        <button type="button" class="btn btn-link btn-sm p-0" data-copy="{{ route('invitations.show', $guest->token) }}" data-copied="{{ __('Copied') }}">{{ __('Copy link') }}</button>
                                        <a href="{{ route('events.guests.edit', [$event, $guest]) }}">{{ __('Edit') }}</a>
                                        <form method="POST" action="{{ route('events.guests.destroy', [$event, $guest]) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Remove :name?', ['name' => $guest->name])) }})">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-link btn-sm text-danger p-0" type="submit">{{ __('Remove') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-body" colspan="5">{{ __('No guests match this list.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $guests->links('pagination::bootstrap-5') }}</div>
    </section>

    <section id="committees" class="mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="mb-1">{{ __('Committees') }}</h2>
                <p class="text-body mb-0">{{ __('Select members from this event’s guest list. Committee members are already ready to receive an invitation.') }}</p>
            </div>
        </div>

        @if ($committeeGuests->isEmpty())
            <div class="alert alert-info">{{ __('Add people to the event guest list before creating a committee.') }}</div>
        @else
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title h6 mb-0">{{ __('Create a committee') }}</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('events.committees.store', $event) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <x-field name="name" label="{{ __('Committee name') }}">
                                    <x-input name="name" value="{{ old('name') }}" maxlength="120" required />
                                </x-field>
                            </div>
                            <div class="col-lg-4">
                                <x-field name="leader_guest_id" label="{{ __('Committee leader') }}">
                                    <select name="leader_guest_id" class="form-select" required>
                                        <option value="">{{ __('Choose a leader') }}</option>
                                        @foreach ($committeeGuests as $committeeGuest)
                                            <option value="{{ $committeeGuest->id }}" @selected((string) old('leader_guest_id') === (string) $committeeGuest->id)>{{ $committeeGuest->name }}{{ $committeeGuest->phone ? ' · '.$committeeGuest->phone : '' }}{{ $committeeGuest->gender ? ' · '.$committeeGuest->gender->label() : '' }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                            </div>
                            <div class="col-lg-4">
                                <x-field name="participant_ids" label="{{ __('Participants') }}">
                                    <select name="participant_ids[]" class="form-select" multiple size="4" required>
                                        @foreach ($committeeGuests as $committeeGuest)
                                            <option value="{{ $committeeGuest->id }}" @selected(in_array((string) $committeeGuest->id, array_map('strval', is_array(old('participant_ids')) ? old('participant_ids') : []), true))>{{ $committeeGuest->name }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                                @foreach ($errors->get('participant_ids.*') as $message)
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @endforeach
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">{{ __('Create committee') }}</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="row g-3">
            @forelse ($event->committees as $committee)
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h3 class="card-title h6 mb-1">{{ $committee->name }}</h3>
                                <small class="text-body">{{ __('Leader') }}: {{ $committee->leader?->name ?? __('Select a new leader') }} · {{ trans_choice(':count participant|:count participants', $committee->guests->count()) }}</small>
                            </div>
                            <form method="POST" action="{{ route('events.committees.destroy', [$event, $committee]) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Remove this committee? Its budget lines will stay in the event budget.')) }})">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('Remove committee') }}</button>
                            </form>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('events.committees.update', [$event, $committee]) }}">
                                @csrf
                                @method('PUT')
                                <div class="row g-3">
                                    <div class="col-lg-4">
                                        <x-field name="name" label="{{ __('Committee name') }}">
                                            <x-input name="name" value="{{ $committee->name }}" maxlength="120" required />
                                        </x-field>
                                    </div>
                                    <div class="col-lg-4">
                                        <x-field name="leader_guest_id" label="{{ __('Committee leader') }}">
                                            <select name="leader_guest_id" class="form-select" required>
                                                <option value="">{{ __('Choose a leader') }}</option>
                                                @foreach ($committeeGuests as $committeeGuest)
                                                    <option value="{{ $committeeGuest->id }}" @selected($committee->leader_guest_id === $committeeGuest->id)>{{ $committeeGuest->name }}{{ $committeeGuest->phone ? ' · '.$committeeGuest->phone : '' }}{{ $committeeGuest->gender ? ' · '.$committeeGuest->gender->label() : '' }}</option>
                                                @endforeach
                                            </select>
                                        </x-field>
                                    </div>
                                    <div class="col-lg-4">
                                        <x-field name="participant_ids" label="{{ __('Participants') }}">
                                            <select name="participant_ids[]" class="form-select" multiple size="4" required>
                                                @foreach ($committeeGuests as $committeeGuest)
                                                    <option value="{{ $committeeGuest->id }}" @selected($committee->guests->contains('id', $committeeGuest->id))>{{ $committeeGuest->name }}</option>
                                                @endforeach
                                            </select>
                                        </x-field>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-secondary" type="submit">{{ __('Save committee') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card"><div class="card-body text-body">{{ __('No committees have been created for this event yet.') }}</div></div>
                </div>
            @endforelse
        </div>
    </section>

    <section id="billing" class="card mt-4">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h2 class="card-title mb-0">{{ __('Package and wallet') }}</h2>
            <a href="{{ route('wallet.index') }}" class="btn btn-sm btn-outline-light btn-shadow">
                <i class="fi fi-rr-wallet me-1"></i> TZS {{ number_format($billing['balance']) }}
            </a>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-stretch">
                <div class="col-lg-5">
                    @if ($event->hasPackage())
                        <div class="border rounded-3 p-3 h-100">
                            <span class="badge bg-primary mb-2">{{ $event->package?->name ?? __('Package') }}</span>
                            <p class="text-body text-sm mb-3">{{ __('Up to :count guests. Spent on this event so far: TZS :amount.', ['count' => number_format($event->package_guest_limit), 'amount' => number_format($billing['spent'])]) }}</p>
                            <ul class="list-unstyled d-grid gap-2 mb-0">
                                @foreach (\App\MessagePurpose::cases() as $purposeCase)
                                    <li class="d-flex justify-content-between">
                                        <span>{{ $purposeCase->label() }}</span>
                                        <strong>{{ __(':count left', ['count' => number_format($billing['allowance'][$purposeCase->value])]) }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="border rounded-3 p-3 h-100">
                            <strong class="d-block mb-1">{{ __('Pay as you go') }}</strong>
                            <p class="text-body text-sm mb-0">{{ __('Each message is paid from your wallet. A package is cheaper when you invite many guests.') }}</p>
                        </div>
                    @endif
                </div>
                <div class="col-lg-7">
                    @if ($billing['upgrades']->isNotEmpty())
                        <form method="POST" action="{{ route('events.package.store', $event) }}" class="border rounded-3 p-3 h-100" data-emca-confirm="{{ __('Pay for this package from your wallet?') }}">
                            @csrf
                            <label class="form-label fw-semibold" for="event-package">{{ $event->hasPackage() ? __('Upgrade package') : __('Buy a package for this event') }}</label>
                            <select class="{{ $control }} mb-2" id="event-package" name="package_id" required>
                                @foreach ($billing['upgrades'] as $packageOption)
                                    <option value="{{ $packageOption->id }}">
                                        {{ $packageOption->name }} · {{ __(':count guests', ['count' => number_format($packageOption->guest_limit)]) }} · TZS {{ number_format($packageOption->price - ($event->hasPackage() ? $event->package_price_paid : 0)) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <x-button>{{ __('Pay from wallet') }}</x-button>
                                <a href="{{ route('packages.index', ['event' => $event->id]) }}" class="text-body text-sm">{{ __('Compare packages') }}</a>
                            </div>
                        </form>
                    @else
                        <div class="border rounded-3 p-3 h-100 text-body text-sm">{{ __('This event has the biggest package available.') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="messages" class="card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">{{ __('Messages') }}</h2>
        </div>
        <div class="card-body">
            <p class="text-body">{{ __('Preparing a message records it here. Open WhatsApp or SMS beside the guest to deliver it.') }}</p>
            <div class="row g-3">
                @foreach ([
                    'events.invitations.send' => [__('Prepare invitations'), \App\MessagePurpose::Invitation],
                    'events.reminders.send' => [__('Prepare reminders'), \App\MessagePurpose::Reminder],
                    'events.thanks.send' => [__('Prepare thank-yous'), \App\MessagePurpose::Thanks],
                ] as $routeName => [$label, $purposeCase])
                    <div class="col-md-4">
                        <form method="POST" action="{{ route($routeName, $event) }}" class="border rounded p-3 h-100 d-flex flex-column">
                            @csrf
                            <select name="channel" class="{{ $control }} mb-2" aria-label="{{ __('Channel') }}">
                                @foreach (\App\MessageChannel::cases() as $channel)
                                    <option value="{{ $channel->value }}">{{ $channel->label() }} · TZS {{ number_format($billing['prices'][$purposeCase->value][$channel->value]) }}</option>
                                @endforeach
                            </select>
                            <small class="text-body mb-3">
                                @if ($billing['allowance'][$purposeCase->value] > 0)
                                    {{ __('Package covers the next :count.', ['count' => number_format($billing['allowance'][$purposeCase->value])]) }}
                                @else
                                    {{ __('Price per guest, paid from your wallet.') }}
                                @endif
                            </small>
                            <x-button class="w-100 mt-auto">{{ $label }}</x-button>
                        </form>
                    </div>
                @endforeach
            </div>
            <ul class="list-group list-group-flush mt-3">
                @forelse ($event->messages as $message)
                    <li class="list-group-item px-0">
                        <p class="fw-semibold mb-0">{{ $message->guest?->name ?? __('Guest') }}, {{ $message->purpose->label() }}, {{ $message->channel->label() }}</p>
                        <p class="text-body mb-1">{{ $message->sent_at?->timezone(config('app.timezone'))->translatedFormat('j M Y, g:i A') }}</p>
                        <p class="mb-0">{{ $message->body }}</p>
                    </li>
                @empty
                    <li class="list-group-item px-0 text-body">{{ __('No messages prepared yet.') }}</li>
                @endforelse
            </ul>
        </div>
    </section>

    <section id="pledges" class="mt-4">
        <h2>{{ __('Pledges') }}</h2>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('events.pledges.store', $event) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="donor_name" label="{{ __('Name') }}">
                                <x-input name="donor_name" value="{{ old('donor_name') }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="phone" label="{{ __('Phone') }}">
                                <x-input name="phone" value="{{ old('phone') }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="amount" label="{{ __('Amount (TZS)') }}">
                                <x-input name="amount" type="text" inputmode="numeric" autocomplete="off" data-emca-money value="{{ old('amount') }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="status" label="{{ __('Status') }}">
                                <select id="status" name="status" class="{{ $control }}" required>
                                    @foreach (\App\PledgeStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected(old('status') === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="guest_id" label="{{ __('Linked guest') }}">
                                <select id="guest_id" name="guest_id" class="{{ $control }}">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($event->guests()->orderBy('name')->get(['id', 'name']) as $pledgeGuest)
                                        <option value="{{ $pledgeGuest->id }}" @selected((string) old('guest_id') === (string) $pledgeGuest->id)>{{ $pledgeGuest->name }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="note" label="{{ __('Note') }}">
                                <x-input name="note" value="{{ old('note') }}" />
                            </x-field>
                        </div>
                    </div>
                    <x-button>{{ __('Add pledge') }}</x-button>
                </form>
            </div>
        </div>
        <div class="card mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Donor') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($event->pledges as $pledge)
                            <tr>
                                <td>
                                    <p class="fw-semibold mb-0">{{ $pledge->donor_name }}</p>
                                    <p class="text-body mb-0">{{ $pledge->note }}</p>
                                </td>
                                <td>TZS {{ number_format($pledge->amount) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('events.pledges.update', [$event, $pledge]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="{{ $control }}" onchange="this.form.submit()">
                                            @foreach (\App\PledgeStatus::cases() as $status)
                                                <option value="{{ $status->value }}" @selected($pledge->status === $status)>{{ $status->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('events.pledges.destroy', [$event, $pledge]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link btn-sm text-danger p-0" type="submit">{{ __('Remove') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-body" colspan="4">{{ __('No pledges yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="budget" class="mt-4">
        <h2>{{ __('Budget') }}</h2>
        <p class="text-body">{{ __('Income TZS :income. Expenses TZS :expense.', ['income' => number_format($income), 'expense' => number_format($expense)]) }}</p>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('events.budget-items.store', $event) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <x-field name="kind" label="{{ __('Kind') }}">
                                <select id="kind" name="kind" class="{{ $control }}" required>
                                    @foreach (\App\BudgetKind::cases() as $kind)
                                        <option value="{{ $kind->value }}" @selected(old('kind') === $kind->value)>{{ $kind->label() }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-4">
                            <x-field name="label" label="{{ __('Label') }}">
                                <x-input name="label" value="{{ old('label') }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-4">
                            <x-field name="amount" label="{{ __('Amount (TZS)') }}">
                                <x-input name="amount" type="text" inputmode="numeric" autocomplete="off" data-emca-money value="{{ old('amount') }}" required />
                            </x-field>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <x-field name="committee_id" label="{{ __('Committee') }}">
                                <select id="committee_id" name="committee_id" class="form-select">
                                    <option value="">{{ __('Event-wide budget') }}</option>
                                    @foreach ($event->committees as $committee)
                                        <option value="{{ $committee->id }}" @selected((string) old('committee_id') === (string) $committee->id)>{{ $committee->name }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-4">
                            <x-field name="sector" label="{{ __('Budget sector') }}">
                                <x-input name="sector" value="{{ old('sector') }}" placeholder="{{ __('For example: Catering') }}" />
                            </x-field>
                        </div>
                        <div class="col-md-4">
                            <x-field name="notes" label="{{ __('Notes') }}">
                                <x-input name="notes" value="{{ old('notes') }}" />
                            </x-field>
                        </div>
                    </div>
                    <x-button>{{ __('Add budget line') }}</x-button>
                </form>
            </div>
        </div>
        <ul class="list-group mt-3">
            @forelse ($event->budgetItems as $item)
                <li class="list-group-item d-flex align-items-center justify-content-between gap-3">
                    <span>
                        <span class="fw-semibold">{{ $item->label }}</span>
                        <span class="text-body">{{ $item->kind->label() }}{{ $item->sector ? ' · '.$item->sector : '' }}{{ $item->committee ? ' · '.$item->committee->name : '' }}, TZS {{ number_format($item->amount) }}</span>
                    </span>
                    <form method="POST" action="{{ route('events.budget-items.destroy', [$event, $item]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-link btn-sm text-danger p-0" type="submit">{{ __('Remove') }}</button>
                    </form>
                </li>
            @empty
                <li class="list-group-item text-body">{{ __('No budget lines yet.') }}</li>
            @endforelse
        </ul>
    </section>

    <section id="tasks" class="mt-4">
        <h2>{{ __('Tasks') }}</h2>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('events.tasks.store', $event) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="title" label="{{ __('Task') }}">
                                <x-input name="title" value="{{ old('title') }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="due_on" label="{{ __('Due') }}">
                                <x-input name="due_on" type="date" value="{{ old('due_on') }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="notes" label="{{ __('Notes') }}">
                                <x-input name="notes" value="{{ old('notes') }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <x-button class="mb-3">{{ __('Add task') }}</x-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <ul class="list-group mt-3">
            @forelse ($event->tasks as $task)
                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <span>
                        <span class="fw-semibold">{{ $task->title }}</span>
                        <span class="text-body">{{ $task->due_on?->translatedFormat('j M Y') }} {{ $task->notes }}</span>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <form method="POST" action="{{ route('events.tasks.update', [$event, $task]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $task->status === \App\TaskStatus::Done ? \App\TaskStatus::Open->value : \App\TaskStatus::Done->value }}">
                            <x-button variant="secondary">{{ $task->status === \App\TaskStatus::Done ? __('Mark open') : __('Mark done') }}</x-button>
                        </form>
                        <form method="POST" action="{{ route('events.tasks.destroy', [$event, $task]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link btn-sm text-danger p-0" type="submit">{{ __('Remove') }}</button>
                        </form>
                    </span>
                </li>
            @empty
                <li class="list-group-item text-body">{{ __('No tasks yet.') }}</li>
            @endforelse
        </ul>
    </section>

    <section id="vendors" class="mt-4">
        <h2>{{ __('Vendors') }}</h2>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('events.vendors.store', $event) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="name" label="{{ __('Name') }}">
                                <x-input name="name" value="{{ old('name') }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="category" label="{{ __('Category') }}">
                                <select id="category" name="category" class="{{ $control }}" required>
                                    @foreach (\App\Http\Requests\StoreVendorRequest::categories() as $category)
                                        <option value="{{ $category }}" @selected(old('category') === $category)>{{ __('labels.vendor_categories.'.$category) }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="phone" label="{{ __('Phone') }}">
                                <x-input name="phone" value="{{ old('phone') }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="price" label="{{ __('Price (TZS)') }}">
                                <x-input name="price" type="text" inputmode="numeric" autocomplete="off" data-emca-money value="{{ old('price') }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="status" label="{{ __('Status') }}">
                                <select id="vendor_status" name="status" class="{{ $control }}" required>
                                    @foreach (\App\VendorStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected(old('status') === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="notes" label="{{ __('Notes') }}">
                                <x-input name="notes" value="{{ old('notes') }}" />
                            </x-field>
                        </div>
                    </div>
                    <x-button>{{ __('Add vendor') }}</x-button>
                </form>
            </div>
        </div>
        <ul class="list-group mt-3">
            @forelse ($event->vendors as $vendor)
                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <span>
                        <span class="fw-semibold">{{ $vendor->name }}</span>
                        <span class="text-body">{{ __('labels.vendor_categories.'.$vendor->category) }}, {{ $vendor->phone }} @if ($vendor->price) , TZS {{ number_format($vendor->price) }} @endif</span>
                    </span>
                    <span class="d-flex align-items-center gap-2">
                        <form method="POST" action="{{ route('events.vendors.update', [$event, $vendor]) }}">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="{{ $control }}" onchange="this.form.submit()">
                                @foreach (\App\VendorStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected($vendor->status === $status)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('events.vendors.destroy', [$event, $vendor]) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link btn-sm text-danger p-0" type="submit">{{ __('Remove') }}</button>
                        </form>
                    </span>
                </li>
            @empty
                <li class="list-group-item text-body">{{ __('No vendors yet.') }}</li>
            @endforelse
        </ul>
    </section>
@endsection
