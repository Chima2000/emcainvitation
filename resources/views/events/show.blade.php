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
        @foreach (['guests' => __('Guests'), 'committees' => __('Committees'), 'check-in' => __('Door'), 'records' => __('Records'), 'messages' => __('Messages'), 'pledges' => __('Pledges'), 'budget' => __('Budget'), 'tasks' => __('Tasks'), 'vendors' => __('Vendors')] as $id => $label)
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
                :lines="$event->weddingCardLines()"
            />
            <div class="d-flex flex-wrap gap-2 mt-3 mb-4">
                <a href="{{ route('events.design.edit', $event) }}" class="btn btn-primary waves-effect waves-light">
                    <i class="fi fi-rr-palette me-1"></i> {{ __('Design the card') }}
                </a>
            </div>
        </div>
        <div class="col-lg-5" id="check-in">
            <div class="card emca-door-checkin" data-door-card data-door-checking="{{ __('Checking the card…') }}">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <h2 class="card-title mb-0">{{ __('Door') }}</h2>
                    <button type="button" class="btn btn-sm btn-light" data-door-refresh>
                        <i class="fi fi-rr-refresh me-1"></i>{{ __('Refresh') }}
                    </button>
                </div>
                <div class="card-body">
                    @if (session('check_in') === 'verified')
                        <div class="alert alert-success mb-3" role="status" data-door-result="verified" data-door-message="{{ session('door_message') }}">{{ session('door_message') }}</div>
                    @elseif (session('check_in') === 'denied')
                        <div class="alert alert-danger mb-3" role="alert" data-door-result="denied" data-door-message="{{ session('door_message') }}">{{ session('door_message') }}</div>
                    @endif
                    <p class="text-body emca-door-checkin-intro">{{ __('Enter the card number printed on the invitation, or scan its code. Only that card admits the guest, and it works once.') }}</p>
                    @if (auth()->user()->isLead())
                        @include('partials.trial-lock')
                    @else
                    <form method="POST" action="{{ route('events.check-ins.store', $event) }}">
                        @csrf
                        @include('events.partials.check-in-scanner')
                    </form>
                    @endif
                    <div class="mt-3">
                        @include('events.partials.record-toggle', [
                            'id' => 'door-arrivals',
                            'show' => __('Show arrivals'),
                            'hide' => __('Hide arrivals'),
                            'count' => $recentCheckIns->count(),
                            'open' => false,
                        ])
                        <div id="door-arrivals" hidden>
                            <ul class="list-group list-group-flush mt-3 mb-0" data-door-arrival-list>
                                @foreach ($recentCheckIns as $checkIn)
                                    <li class="list-group-item px-0" data-door-arrival @if ($loop->index >= 5) hidden @endif>{{ $checkIn->name }}, <span class="font-monospace">{{ $checkIn->serial }}</span>, {{ $checkIn->checked_in_at->timezone(config('app.timezone'))->translatedFormat('g:i A') }}</li>
                                @endforeach
                            </ul>
                            @if ($recentCheckIns->count() > 5)
                                <div class="d-flex justify-content-end gap-2 mt-2" data-door-arrival-pager>
                                    <button type="button" class="btn btn-sm btn-light" data-door-arrival-prev hidden>{{ __('Previous') }}</button>
                                    <button type="button" class="btn btn-sm btn-light" data-door-arrival-next>{{ __('Next') }}</button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section id="guests" class="mt-4">
        <h2 class="mb-3">{{ __('Guests') }}</h2>
        <div class="row">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        @if (auth()->user()->isLead() && $event->trialGuestsRemaining() === 0)
                            @include('partials.trial-lock')
                        @else
                        <form method="POST" action="{{ route('events.guests.store', $event) }}">
                            @csrf
                            <h3 class="h5">{{ __('Add a guest') }}</h3>
                            @if ($contacts->isNotEmpty())
                                <x-field name="contact_id" label="{{ __('From the contact book') }}">
                                    <select id="contact_id" name="contact_id" class="{{ $control }}" data-contact-fill>
                                        <option value="">{{ __('Type a new guest') }}</option>
                                        @foreach ($contacts as $contact)
                                            <option value="{{ $contact->id }}" data-name="{{ $contact->name }}" data-phone="{{ $contact->phone }}" data-email="{{ $contact->email }}" data-gender="{{ $contact->gender?->value }}" @selected((string) old('contact_id') === (string) $contact->id)>{{ $contact->name }}{{ $contact->phone ? ' · '.$contact->phone : '' }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                                <p class="text-body"><a href="{{ route('contacts.index') }}">{{ __('Contact book') }}</a></p>
                            @else
                                <p class="text-body"><a href="{{ route('contacts.index') }}">{{ __('Contact book') }}</a></p>
                            @endif
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
                                    <x-field name="table_name" label="{{ __('Table name') }}">
                                        <x-input name="table_name" value="{{ old('table_name') }}" maxlength="40" />
                                    </x-field>
                                </div>
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
                        @endif
                        <script>
                            document.addEventListener('change', (event) => {
                                const select = event.target.closest('[data-contact-fill]');

                                if (!select) {
                                    return;
                                }

                                const option = select.selectedOptions[0];
                                const form = select.closest('form');

                                if (!option || !form || option.value === '') {
                                    return;
                                }

                                ['name', 'phone', 'email', 'gender'].forEach((name) => {
                                    const field = form.querySelector(`[name="${name}"]`);
                                    const value = option.dataset[name] || '';

                                    if (field && value !== '') {
                                        field.value = value;
                                    }
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        @if (auth()->user()->isLead())
                            <p class="text-body">{{ __('A trial can list :count guests.', ['count' => \App\Models\Event::TrialGuestLimit]) }}</p>
                        @endif
                        @if (auth()->user()->isLead() && $event->trialGuestsRemaining() === 0)
                            @include('partials.trial-lock')
                        @else
                        <form method="POST" action="{{ route('events.guests.import', $event) }}" enctype="multipart/form-data">
                            @csrf
                            <h3 class="h5">{{ __('Import a CSV') }}</h3>
                            <p class="text-body">{{ __('Use the template so each column matches a guest field: name, phone, email, gender, party_size, notes. A header row is optional. Gender is female, male, other, or prefer_not_to_say.') }}</p>
                            <p><a href="{{ route('events.guests.template', $event) }}">{{ __('Download the CSV template') }}</a></p>
                            <x-field name="file" label="{{ __('File') }}">
                                <input id="file" name="file" type="file" accept=".csv,.txt" class="form-control" required>
                            </x-field>
                            <x-button variant="secondary">{{ __('Import guests') }}</x-button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @php
            $guestListOpen = request()->filled('q') || request()->integer('page') > 1;
        @endphp
        <div class="card mt-3">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-light emca-guest-toggle"
                        data-guest-list-toggle
                        data-guest-list-show="{{ __('Show guests') }}"
                        data-guest-list-hide="{{ __('Hide guests') }}"
                        aria-expanded="{{ $guestListOpen ? 'true' : 'false' }}"
                        aria-controls="guest-list"
                    >
                        <span data-guest-list-label>{{ $guestListOpen ? __('Hide guests') : __('Show guests') }}</span>
                    </button>
                    <span class="text-body">({{ $guests->count() }})</span>
                </div>
                <form method="GET" action="{{ route('events.show', $event) }}" class="d-flex flex-nowrap align-items-center gap-2">
                    <input name="q" value="{{ request('q') }}" placeholder="{{ __('Name, phone, or card') }}" class="form-control form-control-sm" style="width: 11rem;" aria-label="{{ __('Name, phone, or card') }}">
                    <x-button variant="secondary" class="btn-sm text-nowrap flex-shrink-0">{{ __('Search') }}</x-button>
                </form>
                @if (request()->filled('q'))
                    <script>
                        if (performance.getEntriesByType('navigation')[0]?.type === 'reload') {
                            const url = new URL(window.location.href);
                            url.searchParams.delete('q');
                            url.searchParams.delete('page');
                            window.location.replace(url.pathname + url.search + url.hash);
                        }
                    </script>
                @endif
            </div>
            <div id="guest-list" data-guest-list{{ $guestListOpen ? '' : ' hidden' }}>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Guest') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Gender') }}</th>
                            <th>{{ __('Card') }}</th>
                            <th>{{ __('Reply') }}</th>
                            <th>{{ __('Door') }}</th>
                            <th class="text-center">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody data-guest-row-list>
                        @forelse ($guests as $guest)
                            <tr data-guest-row @if ($loop->index >= 5) hidden @endif>
                                <td>
                                    <p class="fw-semibold mb-0">{{ $guest->name }}</p>
                                    @if ($guest->phone)
                                        <p class="text-body mb-0">{{ $guest->phone }}</p>
                                    @endif
                                    @if ($guest->table_name)
                                        <p class="text-body mb-0">{{ __('Table') }} {{ $guest->table_name }}</p>
                                    @endif
                                </td>
                                <td>{{ $guest->email ?: '—' }}</td>
                                <td>{{ $guest->gender?->label() ?? '—' }}</td>
                                <td class="font-monospace">{{ $guest->serial }}</td>
                                <td>
                                    {{ $guest->rsvp_status->label() }}, {{ $guest->party_size }}
                                    <span class="d-block text-body">{{ $guest->opened_at ? __('Opened :date', ['date' => $guest->opened_at->timezone(config('app.timezone'))->translatedFormat('j M, g:i A')]) : __('Not opened') }}</span>
                                </td>
                                <td>{{ $guest->checked_in_at ? $guest->checked_in_at->timezone(config('app.timezone'))->translatedFormat('j M, g:i A') : __('Not yet') }}</td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Action') }}">
                                            <i class="fi fi-rr-menu-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if ($guest->whatsAppUrl())
                                                <li><a class="dropdown-item" href="{{ $guest->whatsAppUrl() }}" target="_blank" rel="noopener"><i class="fi fi-brands-whatsapp me-2"></i>WhatsApp</a></li>
                                            @endif
                                            @if ($guest->smsUrl())
                                                <li><a class="dropdown-item" href="{{ $guest->smsUrl() }}"><i class="fi fi-rr-comment me-2"></i>SMS</a></li>
                                            @endif
                                            <li><button type="button" class="dropdown-item" data-copy="{{ route('invitations.show', $guest->token) }}" data-copied="{{ __('Copied') }}">{{ __('Copy link') }}</button></li>
                                            <li><a class="dropdown-item" href="{{ route('events.guests.edit', [$event, $guest]) }}">{{ __('Edit') }}</a></li>
                                            <li>
                                                <form method="POST" action="{{ route('events.guests.destroy', [$event, $guest]) }}" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Remove :name?', ['name' => $guest->name])) }})">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit">{{ __('Remove') }}</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-body" colspan="7">{{ __('No guests match this list.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($guests->count() > 5)
                <div class="d-flex justify-content-end gap-2 px-3 pb-3" data-guest-row-pager>
                    <button type="button" class="btn btn-sm btn-light" data-guest-row-prev hidden>{{ __('Previous') }}</button>
                    <button type="button" class="btn btn-sm btn-light" data-guest-row-next>{{ __('Next') }}</button>
                </div>
            @endif
            </div>
        </div>
        @once
            <script>
                if (!window.emcaGuestListReady) {
                    window.emcaGuestListReady = true;

                    document.addEventListener('click', (event) => {
                        const button = event.target.closest('[data-guest-list-toggle]');

                        if (!button) {
                            return;
                        }

                        const list = document.getElementById(button.getAttribute('aria-controls'));

                        if (!list) {
                            return;
                        }

                        list.hidden = !list.hidden;
                        const open = !list.hidden;
                        button.setAttribute('aria-expanded', open ? 'true' : 'false');

                        const label = button.querySelector('[data-guest-list-label]');

                        if (label) {
                            label.textContent = open ? button.dataset.guestListHide : button.dataset.guestListShow;
                        }
                    });
                }
            </script>
        @endonce
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title h6 mb-0">{{ __('Guest groups') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-body">{{ __('A group is a named set of guests already on this event. You can prepare a message for one group.') }}</p>
                @if ($guests->isNotEmpty())
                    <form method="POST" action="{{ route('events.groups.store', $event) }}" class="mb-3">
                        @csrf
                        <x-field name="name" label="{{ __('Group name') }}">
                            <x-input name="name" value="{{ old('name') }}" maxlength="80" required />
                        </x-field>
                        <div class="mb-3">
                            @foreach ($committeeGuests as $member)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="guest_ids[]" id="group-guest-{{ $member->id }}" value="{{ $member->id }}" @checked(in_array((string) $member->id, array_map('strval', old('guest_ids', [])), true))>
                                    <label class="form-check-label" for="group-guest-{{ $member->id }}">{{ $member->name }}</label>
                                </div>
                            @endforeach
                        </div>
                        <x-button>{{ __('Save group') }}</x-button>
                    </form>
                @endif
                <ul class="list-unstyled mb-0">
                    @forelse ($guestGroups as $group)
                        <li class="d-flex justify-content-between gap-2 py-1">
                            <span>{{ $group->name }} · {{ trans_choice(':count guest|:count guests', $group->guests->count(), ['count' => $group->guests->count()]) }}</span>
                            <form method="POST" action="{{ route('events.groups.destroy', [$event, $group]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link btn-sm px-0" type="submit">{{ __('Remove') }}</button>
                            </form>
                        </li>
                    @empty
                        <li class="text-body">{{ __('No groups yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>

    <section id="committees" class="card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">{{ __('Committees') }}</h2>
        </div>
        <div class="card-body">
        @php
            $doorCommittee = $event->committees->firstWhere('is_door', true);
        @endphp
        @if (session('committee_notice'))
            <div class="alert alert-success" role="status" data-committee-notice data-emca-persist>{{ session('committee_notice') }}</div>
        @endif
        <p class="text-body">{{ __('Select members from this event’s guest list. Committee members are already ready to receive an invitation.') }}</p>

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
                            <div class="col-lg-8">
                                <label class="form-label">{{ __('Committee and participants') }}</label>
                                @include('events.partials.committee-people', [
                                    'guests' => $committeeGuests,
                                    'prefix' => 'create-committee',
                                    'selectedParticipants' => old('participant_ids', []),
                                    'selectedLeader' => old('leader_guest_id'),
                                ])
                                @error('leader_guest_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('participant_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @foreach ($errors->get('participant_ids.*') as $message)
                                    <div class="invalid-feedback d-block">{{ is_array($message) ? implode(' ', $message) : $message }}</div>
                                @endforeach
                            </div>
                        </div>
                        @if ($doorCommittee)
                            <p class="text-body mt-3 mb-3">{{ __('Door committee: :name. Only that committee can hold door links.', ['name' => $doorCommittee->name]) }}</p>
                        @else
                            <div class="border rounded-3 p-3 mt-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_door" id="is_door" value="1" @checked(old('is_door'))>
                                    <label class="form-check-label" for="is_door">{{ __('This committee works the door') }}</label>
                                </div>
                                <p class="form-text">{{ __('Only one committee on this event can work the door. Each member you choose gets their own door link.') }}</p>
                                <p class="form-label mb-1">{{ __('Door link holders') }}</p>
                                @foreach ($committeeGuests as $guest)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="door_guest_ids[]" id="door-guest-{{ $guest->id }}" value="{{ $guest->id }}" @checked(in_array((string) $guest->id, array_map('strval', old('door_guest_ids', [])), true))>
                                        <label class="form-check-label" for="door-guest-{{ $guest->id }}">{{ $guest->name }}</label>
                                    </div>
                                @endforeach
                                @error('door_guest_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @foreach ($errors->get('door_guest_ids.*') as $message)
                                    <div class="invalid-feedback d-block">{{ is_array($message) ? implode(' ', $message) : $message }}</div>
                                @endforeach
                            </div>
                        @endif
                        <button class="btn btn-primary" type="submit">{{ __('Create committee') }}</button>
                    </form>
                </div>
            </div>
        @endif

        @php
            $committeesOpen = filled(old('committee_form')) || session()->has('committee_notice');
        @endphp
        <div class="card mt-3">
            <div class="card-header">
        @include('events.partials.record-toggle', [
            'id' => 'committee-list',
            'show' => __('Show committees'),
            'hide' => __('Hide committees'),
            'count' => $event->committees->count(),
            'open' => $committeesOpen,
        ])
            </div>
        <div class="card-body" id="committee-list"{{ $committeesOpen ? '' : ' hidden' }}>
        <div class="row g-3 align-items-start">
            @forelse ($event->committees as $committee)
                <div class="col-12 col-lg-6">
                    <div class="card emca-committee-card">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <h3 class="card-title h6 mb-1 text-break">{{ $committee->name }}@if ($committee->is_door) <span class="badge bg-light text-dark">{{ __('Door committee') }}</span>@endif</h3>
                                <small class="text-body d-block text-break">{{ __('Leader') }}: {{ $committee->leader?->name ?? __('Select a new leader') }} · {{ trans_choice(':count participant|:count participants', $committee->guests->count()) }}</small>
                            </div>
                            <form class="ms-auto" method="POST" action="{{ route('events.committees.destroy', [$event, $committee]) }}" data-emca-confirm="{{ __('Remove this committee? Its budget lines will stay in the event budget.') }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger text-nowrap" type="submit">{{ __('Remove committee') }}</button>
                            </form>
                        </div>
                        <div class="card-body">
                            @php
                                $committeeMemberIds = $committee->guests->pluck('id')->all();
                                $guestsToAdd = $committeeGuests->whereNotIn('id', $committeeMemberIds);
                            @endphp
                            <ul class="list-unstyled mb-3 emca-committee-members" data-committee-members="{{ $committee->id }}">
                                @foreach ($committee->guests as $member)
                                    <li class="py-1 text-break">
                                        {{ $member->name }}{{ $member->phone ? ' · '.$member->phone : '' }}
                                        @if ($member->id === $committee->leader_guest_id)
                                            <span class="badge bg-light text-dark">{{ __('Committee leader') }}</span>
                                        @endif
                                        @if ($committee->is_door && filled($member->door_token))
                                            <span class="badge bg-light text-dark">{{ __('Door link holder') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            @if ($committee->leader_guest_id && $committeeMemberIds !== [])
                                <form id="save-committee-{{ $committee->id }}" method="POST" action="{{ route('events.committees.update', [$event, $committee]) }}">
                                    @csrf
                                    @method('PUT')
                                    <x-field name="name" for="committee-name-{{ $committee->id }}" label="{{ __('Committee name') }}">
                                            <x-input name="name" id="committee-name-{{ $committee->id }}" class="emca-fit-name" value="{{ $committee->name }}" maxlength="120" required />
                                    </x-field>
                                    <input type="hidden" name="leader_guest_id" value="{{ $committee->leader_guest_id }}">
                                    @foreach ($committeeMemberIds as $memberId)
                                        <input type="hidden" name="participant_ids[]" value="{{ $memberId }}">
                                    @endforeach
                                </form>
                            @endif
                            @php
                                $addPanelOpen = (string) old('committee_form', session('committee_form')) === (string) $committee->id;
                                $showDoorEditor = ! auth()->user()->isLead() && ($committee->is_door || ! $doorCommittee);
                            @endphp
                            <div class="d-flex flex-wrap gap-2">
                                @if ($committee->leader_guest_id && $committeeMemberIds !== [])
                                    <button class="btn btn-sm btn-secondary" type="submit" form="save-committee-{{ $committee->id }}">{{ __('Save committee') }}</button>
                                @endif
                                <button class="btn btn-sm btn-light" type="button" data-committee-add-toggle="add-committee-{{ $committee->id }}" data-committee-add-label="{{ __('Edit committee') }}" data-committee-open-label="{{ __('Close') }}" aria-expanded="{{ $addPanelOpen ? 'true' : 'false' }}" aria-controls="add-committee-{{ $committee->id }}">{{ $addPanelOpen ? __('Close') : __('Edit committee') }}</button>
                            </div>
                            <div class="mt-3" id="add-committee-{{ $committee->id }}" data-committee-add="{{ $committee->id }}" @unless ($addPanelOpen) hidden @endunless>
                                @if ($guestsToAdd->isEmpty())
                                    <p class="text-body mb-0">{{ __('Every guest on this event is already on this committee.') }}</p>
                                @else
                                    <form method="POST" action="{{ route('events.committees.guests.store', [$event, $committee]) }}">
                                        @csrf
                                        <input type="hidden" name="committee_form" value="{{ $committee->id }}">
                                        @include('events.partials.committee-people', [
                                            'guests' => $guestsToAdd,
                                            'prefix' => 'add-committee-'.$committee->id,
                                            'chooseLeader' => false,
                                            'participantField' => 'guest_ids',
                                            'selectedParticipants' => (string) old('committee_form') === (string) $committee->id ? old('guest_ids', []) : [],
                                        ])
                                        @error('participant_ids')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        @foreach ($errors->get('participant_ids.*') as $message)
                                            <div class="invalid-feedback d-block">{{ is_array($message) ? implode(' ', $message) : $message }}</div>
                                        @endforeach
                                        <button class="btn btn-sm btn-primary mt-2" type="submit">{{ __('Add guests') }}</button>
                                    </form>
                                @endif
                                @if ($showDoorEditor)
                                    <div class="border rounded-3 p-3 mt-3">
                                        <h4 class="h6 mb-1">{{ __('Door link') }}</h4>
                                        <p class="text-body mb-2">{{ __('Each member you choose can check guests in at their own door.') }}</p>
                                        @foreach ($committee->guests as $member)
                                            @if ($committee->is_door && filled($member->door_token))
                                                <label class="form-label mb-1" for="door-link-{{ $committee->id }}-{{ $member->id }}">{{ $member->name }}</label>
                                                <input id="door-link-{{ $committee->id }}-{{ $member->id }}" class="form-control form-control-sm mb-2 emca-share-link" value="{{ route('doors.show', $member->door_token) }}" readonly>
                                                <form method="POST" action="{{ route('events.committees.door.close', [$event, $committee]) }}" class="mb-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="committee_form" value="{{ $committee->id }}">
                                                    <input type="hidden" name="guest_id" value="{{ $member->id }}">
                                                    <button class="btn btn-link btn-sm px-0" type="submit">{{ __('Remove :name from the door link', ['name' => $member->name]) }}</button>
                                                </form>
                                            @endif
                                        @endforeach
                                        <form method="POST" action="{{ route('events.committees.door', [$event, $committee]) }}" class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                            @csrf
                                            <input type="hidden" name="committee_form" value="{{ $committee->id }}">
                                            <select name="guest_id" class="{{ $control }}" style="max-width: 16rem;" aria-label="{{ __('Door link holder') }}" required>
                                                @foreach ($committee->guests as $member)
                                                    <option value="{{ $member->id }}">{{ $member->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-sm btn-secondary" type="submit">{{ __('Give the door link') }}</button>
                                        </form>
                                        @if ($committee->is_door)
                                            <form method="POST" action="{{ route('events.committees.door.release', [$event, $committee]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="committee_form" value="{{ $committee->id }}">
                                                <button class="btn btn-link btn-sm px-0" type="submit">{{ __('Remove this committee from the door link') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card"><div class="card-body text-body">{{ __('No committees have been created for this event yet.') }}</div></div>
                </div>
            @endforelse
        </div>
        </div>
        </div>
        </div>
    </section>

    <section id="billing" class="card mt-4">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h2 class="card-title mb-0">{{ __('Package and wallet') }}</h2>
            @unless (auth()->user()->isLead())
                <a href="{{ route('wallet.index') }}" class="btn btn-sm btn-outline-light btn-shadow">
                    <i class="fi fi-rr-wallet me-1"></i> TZS {{ number_format($billing['balance']) }}
                </a>
            @endunless
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
                                    @continue(! $purposeCase->isEventMessage())
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
                    @if (auth()->user()->isLead())
                        @include('partials.trial-lock')
                    @elseif ($billing['upgrades']->isNotEmpty())
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

    <section id="records" class="card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">{{ __('Records') }}</h2>
        </div>
        <div class="card-body">
            <p class="text-body">{{ __('These figures are only for reading. They do not send a message or spend money.') }}</p>
            <ul class="list-unstyled mb-0">
                <li>{{ __('Wallet balance') }}: TZS {{ number_format($billing['balance']) }}</li>
                <li>{{ __('Have not replied') }}: {{ number_format($event->pending_count) }}</li>
                <li>{{ __('Checked in') }}: {{ number_format($event->checked_in_count) }}</li>
                <li>{{ __('Open pledges') }}: {{ number_format($openPledgeCount) }} · TZS {{ number_format($openPledgeTotal) }}</li>
            </ul>
        </div>
    </section>

    <section id="messages" class="card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">{{ __('Messages') }}</h2>
        </div>
        <div class="card-body">
            <p class="text-body">{{ __('Preparing a message records it here. Open WhatsApp or SMS beside the guest to deliver it.') }}</p>
            @if (auth()->user()->isLead())
                @include('partials.trial-lock')
            @else
            <div class="row g-3">
                @foreach ([
                    'events.invitations.send' => [__('Prepare invitations'), \App\MessagePurpose::Invitation],
                    'events.reminders.send' => [__('Prepare reminders'), \App\MessagePurpose::Reminder],
                    'events.thanks.send' => [__('Prepare thank-yous'), \App\MessagePurpose::Thanks],
                ] as $routeName => [$label, $purposeCase])
                    <div class="col-md-4">
                        <form method="POST" action="{{ route($routeName, $event) }}" class="border rounded p-3 h-100 d-flex flex-column">
                            @csrf
                            <input type="hidden" name="purpose" value="{{ $purposeCase->value }}">
                            <p class="fw-semibold">{{ $label }}</p>
                            <select name="audience" class="{{ $control }} mb-2" aria-label="{{ __('Who receives it') }}">
                                <option value="waiting">{{ __('Everyone still waiting') }}</option>
                                <option value="unreplied">{{ __('Only people who have not replied') }}</option>
                                @if ($guestGroups->isNotEmpty())
                                    <option value="group">{{ __('A guest group') }}</option>
                                @endif
                            </select>
                            @if ($guestGroups->isNotEmpty())
                                <select name="group_id" class="{{ $control }} mb-2" aria-label="{{ __('Guest group') }}">
                                    @foreach ($guestGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <select name="channel" class="{{ $control }} mb-2" aria-label="{{ __('Channel') }}">
                                @foreach (\App\MessageChannel::cases() as $channel)
                                    @continue(! $channel->isEventChannel())
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
                            <button class="btn btn-primary w-100 mt-auto" type="submit" formmethod="GET" formaction="{{ route('events.messages.preview', $event) }}">{{ __('Review') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
            <form method="POST" action="{{ route('events.reminder.update', $event) }}" class="border rounded p-3 mt-3">
                @csrf
                @method('PUT')
                <h3 class="h6">{{ __('Automatic reminder') }}</h3>
                <p class="text-body">{{ __('Guests who have not replied get one reminder :days days before the event.', ['days' => $event->reminder_days ?: 3]) }}</p>
                @if ($event->auto_reminder_sent_at)
                    <p class="text-body">{{ __('The automatic reminder was prepared on :date.', ['date' => $event->auto_reminder_sent_at->timezone(config('app.timezone'))->translatedFormat('j F Y, g:i A')]) }}</p>
                @endif
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <x-field name="reminder_days" label="{{ __('Days before the event') }}">
                            <x-input name="reminder_days" type="number" min="1" max="14" value="{{ old('reminder_days', $event->reminder_days ?: 3) }}" required />
                        </x-field>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="auto_reminder" name="auto_reminder" value="1" @checked(old('auto_reminder', $event->auto_reminder))>
                            <label class="form-check-label" for="auto_reminder">{{ __('Automatic reminder') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <x-button class="mb-3">{{ __('Save reminder') }}</x-button>
                    </div>
                </div>
            </form>
            @endif
            <div class="card mt-3">
                <div class="card-header">
                    @include('events.partials.record-toggle', [
                        'id' => 'message-list',
                        'show' => __('Show messages'),
                        'hide' => __('Hide messages'),
                        'count' => $event->messages->count(),
                        'open' => false,
                    ])
                </div>
                <div class="card-body" id="message-list" hidden>
                <ul class="list-group list-group-flush">
                    @forelse ($event->messages as $message)
                        <li class="list-group-item px-0">
                            <p class="fw-semibold mb-0">{{ $message->guest?->name ?? __('Guest') }}, {{ $message->purpose->label() }}, {{ $message->channel->label() }}</p>
                            <p class="text-body mb-1">{{ $message->sent_at?->timezone(config('app.timezone'))->translatedFormat('j M Y, g:i A') }}</p>
                            <p class="mb-0">{!! preg_replace('#(https?://[^\s<]+)#', '<a href="$1" class="emca-share-link" target="_blank" rel="noopener">$1</a>', e($message->body)) !!}</p>
                        </li>
                    @empty
                        <li class="list-group-item px-0 text-body">{{ __('No messages prepared yet.') }}</li>
                    @endforelse
                </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="pledges" class="card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">{{ __('Pledges') }}</h2>
        </div>
        <div class="card-body">
        @unless (auth()->user()->isLead())
            <form method="GET" action="{{ route('events.messages.preview', $event) }}" class="border rounded p-3 mb-3">
                <h3 class="h6">{{ __('Pledge note') }}</h3>
                <p class="text-body">{{ __('Prepare one note for each guest with an open pledge. It uses the same message list, and it tells them their own balance.') }}</p>
                <input type="hidden" name="purpose" value="pledge">
                <input type="hidden" name="audience" value="waiting">
                <select name="channel" class="{{ $control }} mb-2" aria-label="{{ __('Channel') }}">
                    @foreach (\App\MessageChannel::cases() as $channel)
                        @continue(! $channel->isEventChannel())
                        <option value="{{ $channel->value }}">{{ $channel->label() }} · TZS {{ number_format($billing['prices']['pledge'][$channel->value]) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary" type="submit">{{ __('Review') }}</button>
            </form>
        @endunless
        @if (session('pledge_notice'))
            <div class="alert alert-success" role="status" data-pledge-notice data-emca-dismiss>{{ session('pledge_notice') }}</div>
        @endif
        @php
            $addingPledge = ! session()->has('pledge_edit');
            $pledgeGuests = $event->guests()->orderBy('name')->get(['id', 'name', 'phone']);
        @endphp
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('events.pledges.store', $event) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="guest_id" label="{{ __('Linked guest') }}">
                                <select id="guest_id" name="guest_id" class="{{ $control }}">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ($pledgeGuests as $pledgeGuest)
                                        <option value="{{ $pledgeGuest->id }}" data-pledge-phone="{{ $pledgeGuest->phone }}" @selected($addingPledge && (string) old('guest_id') === (string) $pledgeGuest->id)>{{ $pledgeGuest->name }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="donor_name" label="{{ __('Name') }}">
                                <x-input name="donor_name" value="{{ $addingPledge ? old('donor_name') : '' }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="phone" label="{{ __('Phone') }}">
                                <x-input name="phone" value="{{ $addingPledge ? old('phone') : '' }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="amount" label="{{ __('Amount (TZS)') }}">
                                <x-input name="amount" type="text" inputmode="numeric" autocomplete="off" data-emca-money value="{{ $addingPledge ? old('amount') : '' }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="status" label="{{ __('Status') }}">
                                <select id="status" name="status" class="{{ $control }}" required>
                                    @foreach (\App\PledgeStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected($addingPledge && old('status') === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="note" label="{{ __('Note') }}">
                                <x-input name="note" value="{{ $addingPledge ? old('note') : '' }}" />
                            </x-field>
                        </div>
                    </div>
                    <x-button>{{ __('Add pledge') }}</x-button>
                </form>
            </div>
        </div>
        @php
            $pledgesOpen = session()->has('pledge_edit');
        @endphp
        <div class="card mt-3">
            <div class="card-header">
        @include('events.partials.record-toggle', [
            'id' => 'pledge-list',
            'show' => __('Show pledges'),
            'hide' => __('Hide pledges'),
            'count' => $event->pledges->count(),
            'open' => $pledgesOpen,
        ])
            </div>
        <div class="card-body" id="pledge-list"{{ $pledgesOpen ? '' : ' hidden' }}>
        <div class="row g-3 align-items-start">
            @forelse ($event->pledges as $pledge)
                @php
                    $pledgeBag = 'pledge-'.$pledge->id;
                    $editingPledge = (string) session('pledge_edit') === (string) $pledge->id;
                    $pledgeValue = function (string $field, mixed $current) use ($editingPledge): mixed {
                        return $editingPledge ? old($field, $current) : $current;
                    };
                @endphp
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span>
                                    @if ($pledge->guest)
                                        <span class="text-body">{{ $pledge->guest->name }} · </span>
                                    @endif
                                    <span class="fw-semibold">{{ $pledge->donor_name }}</span>
                                    <span class="text-body">TZS {{ number_format($pledge->amount) }} {{ $pledge->note }}</span>
                                </span>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <button class="btn btn-sm btn-light" type="button" data-pledge-edit="pledge-line-{{ $pledge->id }}" aria-expanded="{{ $editingPledge ? 'true' : 'false' }}">{{ __('Edit') }}</button>
                                    <form method="POST" action="{{ route('events.pledges.update', [$event, $pledge]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="{{ $control }}" aria-label="{{ __('Status') }}" onchange="this.form.submit()">
                                            @foreach (\App\PledgeStatus::cases() as $status)
                                                <option value="{{ $status->value }}" @selected($pledge->status === $status)>{{ $status->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('events.pledges.destroy', [$event, $pledge]) }}" data-emca-confirm="{{ __('Remove this pledge?') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('Remove') }}</button>
                                    </form>
                                </div>
                            </div>
                            <form id="pledge-line-{{ $pledge->id }}" class="border-top mt-3 pt-3" method="POST" action="{{ route('events.pledges.update', [$event, $pledge]) }}" @unless ($editingPledge) hidden @endunless>
                                @csrf
                                @method('PATCH')
                                <x-field name="guest_id" :for="'pledge-guest-'.$pledge->id" :bag="$pledgeBag" label="{{ __('Linked guest') }}">
                                    <select id="pledge-guest-{{ $pledge->id }}" name="guest_id" class="{{ $control }}">
                                        <option value="">{{ __('None') }}</option>
                                        @foreach ($pledgeGuests as $pledgeGuest)
                                            <option value="{{ $pledgeGuest->id }}" data-pledge-phone="{{ $pledgeGuest->phone }}" @selected((string) $pledgeValue('guest_id', $pledge->guest_id) === (string) $pledgeGuest->id)>{{ $pledgeGuest->name }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                                <x-field name="donor_name" :for="'pledge-name-'.$pledge->id" :bag="$pledgeBag" label="{{ __('Name') }}">
                                    <x-input :id="'pledge-name-'.$pledge->id" name="donor_name" :value="$pledgeValue('donor_name', $pledge->donor_name)" :bag="$pledgeBag" required />
                                </x-field>
                                <x-field name="phone" :for="'pledge-phone-'.$pledge->id" :bag="$pledgeBag" label="{{ __('Phone') }}">
                                    <x-input :id="'pledge-phone-'.$pledge->id" name="phone" :value="$pledgeValue('phone', $pledge->phone)" :bag="$pledgeBag" />
                                </x-field>
                                <x-field name="amount" :for="'pledge-amount-'.$pledge->id" :bag="$pledgeBag" label="{{ __('Amount (TZS)') }}">
                                    <x-input :id="'pledge-amount-'.$pledge->id" name="amount" type="text" inputmode="numeric" autocomplete="off" data-emca-money :value="$pledgeValue('amount', $pledge->amount)" :bag="$pledgeBag" required />
                                </x-field>
                                <x-field name="status" :for="'pledge-status-'.$pledge->id" :bag="$pledgeBag" label="{{ __('Status') }}">
                                    <select id="pledge-status-{{ $pledge->id }}" name="status" class="{{ $control }}" required>
                                        @foreach (\App\PledgeStatus::cases() as $status)
                                            <option value="{{ $status->value }}" @selected($pledgeValue('status', $pledge->status->value) === $status->value)>{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                                <x-field name="note" :for="'pledge-note-'.$pledge->id" :bag="$pledgeBag" label="{{ __('Note') }}">
                                    <x-input :id="'pledge-note-'.$pledge->id" name="note" :value="$pledgeValue('note', $pledge->note)" :bag="$pledgeBag" />
                                </x-field>
                                <button class="btn btn-primary" type="submit">{{ __('Save pledge') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card"><div class="card-body text-body">{{ __('No pledges yet.') }}</div></div>
                </div>
            @endforelse
        </div>
        </div>
        </div>
        <script>
            if (!window.emcaPledgeEditReady) {
                window.emcaPledgeEditReady = true;

                document.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-pledge-edit]');

                    if (!button) {
                        return;
                    }

                    const panel = document.getElementById(button.getAttribute('data-pledge-edit'));

                    if (!panel) {
                        return;
                    }

                    panel.hidden = !panel.hidden;
                    button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
                });

                document.addEventListener('change', (event) => {
                    const select = event.target.closest('#pledges select[name="guest_id"]');

                    if (!select || !select.form) {
                        return;
                    }

                    const phone = select.form.querySelector('input[name="phone"]');
                    const chosen = select.selectedOptions[0];

                    if (!phone) {
                        return;
                    }

                    phone.value = chosen?.dataset.pledgePhone ?? '';
                });
            }
        </script>
        </div>
    </section>

    <section id="budget" class="card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">{{ __('Budget') }}</h2>
        </div>
        <div class="card-body">
        @if (session('budget_notice'))
            <div class="alert alert-success" role="status" data-budget-notice data-emca-dismiss>{{ session('budget_notice') }}</div>
        @endif
        <p class="text-body">{{ __('Income TZS :income. Expenses TZS :expense.', ['income' => number_format($income), 'expense' => number_format($expense)]) }}</p>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('events.budget-items.store', $event) }}">
                    @csrf
                    @php
                        $addingBudget = ! session()->has('budget_edit');
                    @endphp
                    <div class="row">
                        <div class="col-md-4">
                            <x-field name="kind" label="{{ __('Kind') }}">
                                <select id="kind" name="kind" class="{{ $control }}" required>
                                    @foreach (\App\BudgetKind::cases() as $kind)
                                        <option value="{{ $kind->value }}" @selected($addingBudget && old('kind') === $kind->value)>{{ $kind->label() }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-4">
                            <x-field name="label" label="{{ __('Label') }}">
                                <x-input name="label" value="{{ $addingBudget ? old('label') : '' }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-4">
                            <x-field name="amount" label="{{ __('Amount (TZS)') }}">
                                <x-input name="amount" type="text" inputmode="numeric" autocomplete="off" data-emca-money value="{{ $addingBudget ? old('amount') : '' }}" required />
                            </x-field>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <x-field name="committee_id" label="{{ __('Committee') }}">
                                <select id="committee_id" name="committee_id" class="form-select">
                                    <option value="">{{ __('Event-wide budget') }}</option>
                                    @foreach ($event->committees as $committee)
                                        <option value="{{ $committee->id }}" @selected($addingBudget && (string) old('committee_id') === (string) $committee->id)>{{ $committee->name }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-4">
                            <x-field name="sector" label="{{ __('Budget sector') }}">
                                <x-input name="sector" value="{{ $addingBudget ? old('sector') : '' }}" placeholder="{{ __('For example: Catering') }}" />
                            </x-field>
                        </div>
                        <div class="col-md-4">
                            <x-field name="notes" label="{{ __('Notes') }}">
                                <x-input name="notes" value="{{ $addingBudget ? old('notes') : '' }}" />
                            </x-field>
                        </div>
                    </div>
                    <x-button>{{ __('Add budget line') }}</x-button>
                </form>
            </div>
        </div>
        @php
            $budgetOpen = session()->has('budget_edit');
        @endphp
        <div class="card mt-3">
            <div class="card-header">
        @include('events.partials.record-toggle', [
            'id' => 'budget-list',
            'show' => __('Show budget'),
            'hide' => __('Hide budget'),
            'count' => $event->budgetItems->count(),
            'open' => $budgetOpen,
        ])
            </div>
        <div class="card-body" id="budget-list"{{ $budgetOpen ? '' : ' hidden' }}>
        <div class="row g-3 align-items-start">
            @forelse ($event->budgetItems as $item)
                @php
                    $budgetBag = 'budget-'.$item->id;
                    $editingBudget = (string) session('budget_edit') === (string) $item->id;
                    $budgetValue = function (string $field, mixed $current) use ($editingBudget): mixed {
                        return $editingBudget ? old($field, $current) : $current;
                    };
                @endphp
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span>
                            <span class="fw-semibold">{{ $item->label }}</span>
                            <span class="text-body">{{ $item->kind->label() }}{{ $item->sector ? ' · '.$item->sector : '' }}{{ $item->committee ? ' · '.$item->committee->name : '' }}, TZS {{ number_format($item->amount) }}</span>
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-light" type="button" data-budget-edit="budget-line-{{ $item->id }}" aria-expanded="{{ $editingBudget ? 'true' : 'false' }}">{{ __('Edit') }}</button>
                            <form method="POST" action="{{ route('events.budget-items.destroy', [$event, $item]) }}" data-emca-confirm="{{ __('Remove this budget line?') }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('Remove') }}</button>
                            </form>
                        </div>
                    </div>
                    <form id="budget-line-{{ $item->id }}" class="border-top mt-3 pt-3" method="POST" action="{{ route('events.budget-items.update', [$event, $item]) }}" @unless ($editingBudget) hidden @endunless>
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-12">
                                <x-field name="kind" :for="'budget-kind-'.$item->id" :bag="$budgetBag" label="{{ __('Kind') }}">
                                    <select id="budget-kind-{{ $item->id }}" name="kind" class="{{ $control }}" required>
                                        @foreach (\App\BudgetKind::cases() as $kind)
                                            <option value="{{ $kind->value }}" @selected($budgetValue('kind', $item->kind->value) === $kind->value)>{{ $kind->label() }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                            </div>
                            <div class="col-12">
                                <x-field name="label" :for="'budget-label-'.$item->id" :bag="$budgetBag" label="{{ __('Label') }}">
                                    <x-input :id="'budget-label-'.$item->id" name="label" :value="$budgetValue('label', $item->label)" :bag="$budgetBag" required />
                                </x-field>
                            </div>
                            <div class="col-12">
                                <x-field name="amount" :for="'budget-amount-'.$item->id" :bag="$budgetBag" label="{{ __('Amount (TZS)') }}">
                                    <x-input :id="'budget-amount-'.$item->id" name="amount" type="text" inputmode="numeric" autocomplete="off" data-emca-money :value="$budgetValue('amount', $item->amount)" :bag="$budgetBag" required />
                                </x-field>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <x-field name="committee_id" :for="'budget-committee-'.$item->id" :bag="$budgetBag" label="{{ __('Committee') }}">
                                    <select id="budget-committee-{{ $item->id }}" name="committee_id" class="form-select">
                                        <option value="">{{ __('Event-wide budget') }}</option>
                                        @foreach ($event->committees as $committee)
                                            <option value="{{ $committee->id }}" @selected((string) $budgetValue('committee_id', $item->committee_id) === (string) $committee->id)>{{ $committee->name }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                            </div>
                            <div class="col-12">
                                <x-field name="sector" :for="'budget-sector-'.$item->id" :bag="$budgetBag" label="{{ __('Budget sector') }}">
                                    <x-input :id="'budget-sector-'.$item->id" name="sector" :value="$budgetValue('sector', $item->sector)" :bag="$budgetBag" placeholder="{{ __('For example: Catering') }}" />
                                </x-field>
                            </div>
                            <div class="col-12">
                                <x-field name="notes" :for="'budget-notes-'.$item->id" :bag="$budgetBag" label="{{ __('Notes') }}">
                                    <x-input :id="'budget-notes-'.$item->id" name="notes" :value="$budgetValue('notes', $item->notes)" :bag="$budgetBag" />
                                </x-field>
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">{{ __('Save budget line') }}</button>
                    </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card"><div class="card-body text-body">{{ __('No budget lines yet.') }}</div></div>
                </div>
            @endforelse
        </div>
        </div>
        </div>
        <script>
            if (!window.emcaBudgetEditReady) {
                window.emcaBudgetEditReady = true;

                document.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-budget-edit]');

                    if (!button) {
                        return;
                    }

                    const panel = document.getElementById(button.getAttribute('data-budget-edit'));

                    if (!panel) {
                        return;
                    }

                    panel.hidden = !panel.hidden;
                    button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
                });
            }
        </script>
        </div>
    </section>

    <section id="tasks" class="card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">{{ __('Tasks') }}</h2>
        </div>
        <div class="card-body">
        @if (session('task_notice'))
            <div class="alert alert-success" role="status" data-task-notice data-emca-dismiss>{{ session('task_notice') }}</div>
        @endif
        @php
            $taskDueMin = now()->timezone(config('app.timezone'))->toDateString();
            $addingTask = ! session()->has('task_edit');
        @endphp
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('events.tasks.store', $event) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="title" label="{{ __('Task') }}">
                                <x-input name="title" value="{{ $addingTask ? old('title') : '' }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="due_on" label="{{ __('Due') }}">
                                <x-input name="due_on" type="date" min="{{ $taskDueMin }}" value="{{ $addingTask ? old('due_on') : '' }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="notes" label="{{ __('Notes') }}">
                                <x-input name="notes" value="{{ $addingTask ? old('notes') : '' }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <x-button class="mb-3">{{ __('Add task') }}</x-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @php
            $tasksOpen = session()->has('task_edit');
        @endphp
        <div class="card mt-3">
            <div class="card-header">
        @include('events.partials.record-toggle', [
            'id' => 'task-list',
            'show' => __('Show tasks'),
            'hide' => __('Hide tasks'),
            'count' => $event->tasks->count(),
            'open' => $tasksOpen,
        ])
            </div>
        <div class="card-body" id="task-list"{{ $tasksOpen ? '' : ' hidden' }}>
        <div class="row g-3 align-items-start">
            @forelse ($event->tasks as $task)
                @php
                    $taskBag = 'task-'.$task->id;
                    $editingTask = (string) session('task_edit') === (string) $task->id;
                    $taskValue = function (string $field, mixed $current) use ($editingTask): mixed {
                        return $editingTask ? old($field, $current) : $current;
                    };
                @endphp
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span>
                                    <span class="fw-semibold">{{ $task->title }}</span>
                                    <span class="text-body">{{ $task->due_on?->translatedFormat('j M Y') }} {{ $task->notes }}</span>
                                </span>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <button class="btn btn-sm btn-light" type="button" data-task-edit="task-line-{{ $task->id }}" aria-expanded="{{ $editingTask ? 'true' : 'false' }}">{{ __('Edit') }}</button>
                                    <form method="POST" action="{{ route('events.tasks.update', [$event, $task]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $task->status === \App\TaskStatus::Done ? \App\TaskStatus::Open->value : \App\TaskStatus::Done->value }}">
                                        <x-button variant="secondary">{{ $task->status === \App\TaskStatus::Done ? __('Mark open') : __('Mark done') }}</x-button>
                                    </form>
                                    <form method="POST" action="{{ route('events.tasks.destroy', [$event, $task]) }}" data-emca-confirm="{{ __('Remove this task?') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('Remove') }}</button>
                                    </form>
                                </div>
                            </div>
                            <form id="task-line-{{ $task->id }}" class="border-top mt-3 pt-3" method="POST" action="{{ route('events.tasks.update', [$event, $task]) }}" @unless ($editingTask) hidden @endunless>
                                @csrf
                                @method('PATCH')
                                <x-field name="title" :for="'task-title-'.$task->id" :bag="$taskBag" label="{{ __('Task') }}">
                                    <x-input :id="'task-title-'.$task->id" name="title" :value="$taskValue('title', $task->title)" :bag="$taskBag" required />
                                </x-field>
                                <x-field name="due_on" :for="'task-due-'.$task->id" :bag="$taskBag" label="{{ __('Due') }}">
                                    <x-input :id="'task-due-'.$task->id" name="due_on" type="date" min="{{ $taskDueMin }}" :value="$taskValue('due_on', $task->due_on?->toDateString())" :bag="$taskBag" />
                                </x-field>
                                <x-field name="notes" :for="'task-notes-'.$task->id" :bag="$taskBag" label="{{ __('Notes') }}">
                                    <x-input :id="'task-notes-'.$task->id" name="notes" :value="$taskValue('notes', $task->notes)" :bag="$taskBag" />
                                </x-field>
                                <button class="btn btn-primary" type="submit">{{ __('Save task') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card"><div class="card-body text-body">{{ __('No tasks yet.') }}</div></div>
                </div>
            @endforelse
        </div>
        </div>
        </div>
        <script>
            if (!window.emcaTaskEditReady) {
                window.emcaTaskEditReady = true;

                document.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-task-edit]');

                    if (!button) {
                        return;
                    }

                    const panel = document.getElementById(button.getAttribute('data-task-edit'));

                    if (!panel) {
                        return;
                    }

                    panel.hidden = !panel.hidden;
                    button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
                });
            }
        </script>
        </div>
    </section>

    <section id="vendors" class="card mt-4">
        <div class="card-header">
            <h2 class="card-title mb-0">{{ __('Vendors') }}</h2>
        </div>
        <div class="card-body">
        @if (session('vendor_notice'))
            <div class="alert alert-success" role="status" data-vendor-notice data-emca-dismiss>{{ session('vendor_notice') }}</div>
        @endif
        @php
            $addingVendor = ! session()->has('vendor_edit');
        @endphp
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('events.vendors.store', $event) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="name" label="{{ __('Name') }}">
                                <x-input name="name" value="{{ $addingVendor ? old('name') : '' }}" required />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="category" label="{{ __('Category') }}">
                                <select id="category" name="category" class="{{ $control }}" required>
                                    @foreach (\App\Http\Requests\StoreVendorRequest::categories() as $category)
                                        <option value="{{ $category }}" @selected($addingVendor && old('category') === $category)>{{ __('labels.vendor_categories.'.$category) }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="phone" label="{{ __('Phone') }}">
                                <x-input name="phone" value="{{ $addingVendor ? old('phone') : '' }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="price" label="{{ __('Price (TZS)') }}">
                                <x-input name="price" type="text" inputmode="numeric" autocomplete="off" data-emca-money value="{{ $addingVendor ? old('price') : '' }}" />
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="status" label="{{ __('Status') }}">
                                <select id="vendor_status" name="status" class="{{ $control }}" required>
                                    @foreach (\App\VendorStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected($addingVendor && old('status') === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                        <div class="col-md-6">
                            <x-field name="notes" label="{{ __('Notes') }}">
                                <x-input name="notes" value="{{ $addingVendor ? old('notes') : '' }}" />
                            </x-field>
                        </div>
                    </div>
                    <x-button>{{ __('Add vendor') }}</x-button>
                </form>
            </div>
        </div>
        @php
            $vendorsOpen = session()->has('vendor_edit');
        @endphp
        <div class="card mt-3">
            <div class="card-header">
        @include('events.partials.record-toggle', [
            'id' => 'vendor-list',
            'show' => __('Show vendors'),
            'hide' => __('Hide vendors'),
            'count' => $event->vendors->count(),
            'open' => $vendorsOpen,
        ])
            </div>
        <div class="card-body" id="vendor-list"{{ $vendorsOpen ? '' : ' hidden' }}>
        <div class="row g-3 align-items-start">
            @forelse ($event->vendors as $vendor)
                @php
                    $vendorBag = 'vendor-'.$vendor->id;
                    $editingVendor = (string) session('vendor_edit') === (string) $vendor->id;
                    $vendorValue = function (string $field, mixed $current) use ($editingVendor): mixed {
                        return $editingVendor ? old($field, $current) : $current;
                    };
                @endphp
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <span>
                                    <span class="fw-semibold">{{ $vendor->name }}</span>
                                    <span class="text-body">{{ __('labels.vendor_categories.'.$vendor->category) }}, {{ $vendor->phone }} @if ($vendor->price) , TZS {{ number_format($vendor->price) }} @endif</span>
                                </span>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <button class="btn btn-sm btn-light" type="button" data-vendor-edit="vendor-line-{{ $vendor->id }}" aria-expanded="{{ $editingVendor ? 'true' : 'false' }}">{{ __('Edit') }}</button>
                                    <form method="POST" action="{{ route('events.vendors.update', [$event, $vendor]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="{{ $control }}" aria-label="{{ __('Status') }}" onchange="this.form.submit()">
                                            @foreach (\App\VendorStatus::cases() as $status)
                                                <option value="{{ $status->value }}" @selected($vendor->status === $status)>{{ $status->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('events.vendors.destroy', [$event, $vendor]) }}" data-emca-confirm="{{ __('Remove this vendor?') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ __('Remove') }}</button>
                                    </form>
                                </div>
                            </div>
                            <form id="vendor-line-{{ $vendor->id }}" class="border-top mt-3 pt-3" method="POST" action="{{ route('events.vendors.update', [$event, $vendor]) }}" @unless ($editingVendor) hidden @endunless>
                                @csrf
                                @method('PATCH')
                                <x-field name="name" :for="'vendor-name-'.$vendor->id" :bag="$vendorBag" label="{{ __('Name') }}">
                                    <x-input :id="'vendor-name-'.$vendor->id" name="name" :value="$vendorValue('name', $vendor->name)" :bag="$vendorBag" required />
                                </x-field>
                                <x-field name="category" :for="'vendor-category-'.$vendor->id" :bag="$vendorBag" label="{{ __('Category') }}">
                                    <select id="vendor-category-{{ $vendor->id }}" name="category" class="{{ $control }}" required>
                                        @foreach (\App\Http\Requests\StoreVendorRequest::categories() as $category)
                                            <option value="{{ $category }}" @selected($vendorValue('category', $vendor->category) === $category)>{{ __('labels.vendor_categories.'.$category) }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                                <x-field name="phone" :for="'vendor-phone-'.$vendor->id" :bag="$vendorBag" label="{{ __('Phone') }}">
                                    <x-input :id="'vendor-phone-'.$vendor->id" name="phone" :value="$vendorValue('phone', $vendor->phone)" :bag="$vendorBag" />
                                </x-field>
                                <x-field name="price" :for="'vendor-price-'.$vendor->id" :bag="$vendorBag" label="{{ __('Price (TZS)') }}">
                                    <x-input :id="'vendor-price-'.$vendor->id" name="price" type="text" inputmode="numeric" autocomplete="off" data-emca-money :value="$vendorValue('price', $vendor->price)" :bag="$vendorBag" />
                                </x-field>
                                <x-field name="status" :for="'vendor-status-'.$vendor->id" :bag="$vendorBag" label="{{ __('Status') }}">
                                    <select id="vendor-status-{{ $vendor->id }}" name="status" class="{{ $control }}" required>
                                        @foreach (\App\VendorStatus::cases() as $status)
                                            <option value="{{ $status->value }}" @selected($vendorValue('status', $vendor->status->value) === $status->value)>{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                </x-field>
                                <x-field name="notes" :for="'vendor-notes-'.$vendor->id" :bag="$vendorBag" label="{{ __('Notes') }}">
                                    <x-input :id="'vendor-notes-'.$vendor->id" name="notes" :value="$vendorValue('notes', $vendor->notes)" :bag="$vendorBag" />
                                </x-field>
                                <button class="btn btn-primary" type="submit">{{ __('Save vendor') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card"><div class="card-body text-body">{{ __('No vendors yet.') }}</div></div>
                </div>
            @endforelse
        </div>
        </div>
        </div>
        <script>
            if (!window.emcaVendorEditReady) {
                window.emcaVendorEditReady = true;

                document.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-vendor-edit]');

                    if (!button) {
                        return;
                    }

                    const panel = document.getElementById(button.getAttribute('data-vendor-edit'));

                    if (!panel) {
                        return;
                    }

                    panel.hidden = !panel.hidden;
                    button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
                });
            }
        </script>
        </div>
    </section>
@endsection
