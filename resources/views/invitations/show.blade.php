@extends('layouts.marketing')

@section('title', $event->title)

@section('content')
    @include('layouts.partials.breadcrumb', [
        'title' => $event->title,
        'photoUrl' => $event->type?->photoUrl(true),
        'lead' => $event->hostsLabel(),
    ])

    <section class="section-padding fix">
        <div class="auto-container">
            <div class="row g-5 align-items-start">
                <div class="col-lg-6 wow fadeInLeft" data-wow-delay="200ms">
                    <x-invitation-card
                        :design="$event->cardDesign()"
                        :kicker="$event->cardKicker()"
                        :title="$event->title"
                        :hosts="$event->hostsLabel()"
                        :headline="$event->cardHeadline()"
                        :when="$event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A')"
                        :venue="$event->venue"
                        :message="$event->cardMessage()"
                        :dress-code="$event->dress_code"
                        :dress-code-colors="$event->dress_code_colors ?? []"
                        :qr="route('invitations.show', $guest->token)"
                        :serial="$guest->serial"
                    />
                </div>
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="300ms" data-emca-region="rsvp">
                    <div class="sec-title mb-30">
                        <div class="h6 sub-title">{{ __('This card is for') }}</div>
                        <h2 class="h2 title">{{ $guest->name }}</h2>
                    </div>
                    <p class="text">{{ __('Show the card number or the code at the door. It can be used once.') }}</p>
                    <form method="POST" action="{{ route('invitations.rsvp', $guest->token) }}" class="emca-form emca-panel mt-30">
                        @csrf
                        <h3 class="h4 mb-20">{{ __('Will you attend?') }}</h3>
                        <div class="emca-rsvp-options">
                            @foreach ([[\App\RsvpStatus::Attending, __('Yes, I will attend'), 'fa-circle-check'], [\App\RsvpStatus::Declined, __('No, I cannot attend'), 'fa-circle-xmark']] as [$status, $label, $icon])
                                <label class="emca-rsvp-option">
                                    <input type="radio" name="rsvp_status" value="{{ $status->value }}" @checked(old('rsvp_status', $guest->rsvp_status->value) === $status->value) required>
                                    <span><i class="fa-light {{ $icon }}"></i> {{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('rsvp_status')<p class="form-error">{{ $message }}</p>@enderror
                        <div class="mt-20 mb-30">
                            <input class="form-control" type="number" min="1" max="30" name="party_size" value="{{ old('party_size', $guest->party_size) }}" placeholder="{{ __('How many people, if you are coming?') }}" aria-label="{{ __('How many people, if you are coming?') }}">
                            @error('party_size')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        <x-site-button>{{ __('Save reply') }}</x-site-button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        document.querySelectorAll('[data-qr]').forEach((node) => {
            if (!window.QRCode || !node.dataset.qr) {
                return;
            }
            new window.QRCode(node, {
                text: node.dataset.qr,
                width: 144,
                height: 144,
                colorDark: '#16171a',
                colorLight: '#ffffff',
                correctLevel: window.QRCode.CorrectLevel.M,
            });
        });
    </script>
@endpush
