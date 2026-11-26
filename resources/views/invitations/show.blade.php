@extends('layouts.marketing')

@section('title', $event->title)

@push('head')
    @php
        $shareImage = $event->cardDesign()['background_url'] ?? $event->type?->photoUrl();

        if (is_string($shareImage) && ! str_starts_with($shareImage, 'http')) {
            $shareImage = url($shareImage);
        }
    @endphp
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __(':hosts invites you to :title', ['hosts' => $event->hostsLabel(), 'title' => $event->title]) }}">
    <meta property="og:description" content="{{ __('On :when at :venue', ['when' => $event->event_date->timezone(config('app.timezone'))->translatedFormat('l, j F Y, g:i A'), 'venue' => $event->venue]) }}">
    <meta property="og:url" content="{{ route('invitations.show', $guest->token) }}">
    @if (filled($shareImage))
        <meta property="og:image" content="{{ $shareImage }}">
    @endif
@endpush

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
                    <div class="emca-guest-card" data-guest-card>
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
                            :lines="$event->weddingCardLines()"
                            :guest-name="$guest->name"
                            :qr="route('invitations.show', $guest->token)"
                            :serial="$guest->serial"
                        />
                        <div class="emca-card-actions">
                            <button type="button" data-card-download data-card-file="{{ $guest->serial }}.pdf">{{ __('Download') }}</button>
                            <button type="button" data-card-print>{{ __('Print') }}</button>
                        </div>
                    </div>
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
        function cardPdf(blob) {
            const pageWidth = 419.53;

            return createImageBitmap(blob).then(function (bitmap) {
                const canvas = document.createElement('canvas');
                canvas.width = bitmap.width;
                canvas.height = bitmap.height;
                const context = canvas.getContext('2d');
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(bitmap, 0, 0);

                return new Promise(function (resolve) {
                    canvas.toBlob(function (jpeg) {
                        resolve(jpeg.arrayBuffer().then(function (buffer) {
                            return pdfDocument(canvas.width, canvas.height, new Uint8Array(buffer), pageWidth, pageWidth * canvas.height / canvas.width);
                        }));
                    }, 'image/jpeg', 0.95);
                });
            });
        }

        function pdfDocument(width, height, jpeg, pageWidth, pageHeight) {
            const encoder = new TextEncoder();
            const chunks = [];
            let offset = 0;
            const offsets = [0];

            function add(bytes) {
                chunks.push(bytes);
                offset += bytes.length;
            }

            function text(value) {
                add(encoder.encode(value));
            }

            function object(body) {
                offsets.push(offset);
                text(offsets.length - 1 + ' 0 obj\n');
                add(body);
                text('\nendobj\n');
            }

            text('%PDF-1.4\n');
            object(encoder.encode('<< /Type /Catalog /Pages 2 0 R >>'));
            object(encoder.encode('<< /Type /Pages /Kids [3 0 R] /Count 1 >>'));
            object(encoder.encode('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' + pageWidth.toFixed(2) + ' ' + pageHeight.toFixed(2) + '] /Contents 4 0 R /Resources << /XObject << /Im0 5 0 R >> >> >>'));

            const draw = encoder.encode('q ' + pageWidth.toFixed(2) + ' 0 0 ' + pageHeight.toFixed(2) + ' 0 0 cm /Im0 Do Q');
            object(concatBytes([
                encoder.encode('<< /Length ' + draw.length + ' >>\nstream\n'),
                draw,
                encoder.encode('\nendstream'),
            ]));
            object(concatBytes([
                encoder.encode('<< /Type /XObject /Subtype /Image /Width ' + width + ' /Height ' + height + ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' + jpeg.length + ' >>\nstream\n'),
                jpeg,
                encoder.encode('\nendstream'),
            ]));

            const xref = offset;
            text('xref\n0 ' + offsets.length + '\n');
            text('0000000000 65535 f \n');
            offsets.slice(1).forEach(function (start) {
                text(String(start).padStart(10, '0') + ' 00000 n \n');
            });
            text('trailer << /Size ' + offsets.length + ' /Root 1 0 R >>\nstartxref\n' + xref + '\n%%EOF');

            return new Blob(chunks, { type: 'application/pdf' });
        }

        function concatBytes(parts) {
            const total = parts.reduce(function (sum, part) {
                return sum + part.length;
            }, 0);
            const bytes = new Uint8Array(total);
            let position = 0;
            parts.forEach(function (part) {
                bytes.set(part, position);
                position += part.length;
            });

            return bytes;
        }

        document.querySelector('[data-card-print]')?.addEventListener('click', function () {
            window.print();
        });

        document.querySelector('[data-card-download]')?.addEventListener('click', async function (event) {
            const button = event.currentTarget;
            const card = document.querySelector('[data-guest-card] .jp-card');

            if (!card) {
                return;
            }

            const label = button.textContent;
            button.disabled = true;
            button.textContent = @json(__('Saving…'));
            const previousScroll = window.scrollY;
            const previousTransform = card.style.transform;
            const previousOrigin = card.style.transformOrigin;

            try {
                const box = card.getBoundingClientRect();
                const fit = Math.min(1, (window.innerHeight - 16) / box.height, (window.innerWidth - 16) / box.width);

                if (fit < 1) {
                    card.style.transformOrigin = 'top center';
                    card.style.transform = 'scale(' + fit + ')';
                }

                card.scrollIntoView({ block: 'center' });
                await new Promise(function (resolve) {
                    window.requestAnimationFrame(function () {
                        window.requestAnimationFrame(resolve);
                    });
                });

                const { toBlob } = await import('https://cdn.jsdelivr.net/npm/html-to-image@1.11.13/+esm');
                const blob = await toBlob(card, {
                    pixelRatio: Math.min(3, 2 / fit),
                    filter(node) {
                        return !(node.tagName === 'IMG' && ! node.getAttribute('src'));
                    },
                });

                if (!blob) {
                    throw new Error('empty');
                }

                const pdf = await cardPdf(blob);
                const link = document.createElement('a');
                link.href = URL.createObjectURL(pdf);
                link.download = button.dataset.cardFile || 'invitation.pdf';
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(function () {
                    URL.revokeObjectURL(link.href);
                }, 1500);
            } catch (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }

                window.alert(@json(__('The card could not be saved on this phone. Print it instead.')));
            } finally {
                card.style.transform = previousTransform;
                card.style.transformOrigin = previousOrigin;
                window.scrollTo(0, previousScroll);
                button.disabled = false;
                button.textContent = label;
            }
        });
    </script>
@endpush

@push('head')
    <style>
        .emca-card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 12px;
        }

        .emca-card-actions button {
            min-height: 40px;
            border: 0;
            border-radius: 8px;
            background: #940000;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 650;
        }

        .emca-card-actions button[data-card-print] {
            background: #1f1f1f;
        }

        .emca-card-actions button:disabled {
            opacity: 0.7;
        }

        @media (max-width: 767.98px) {
            .emca-card-actions {
                gap: 6px;
                margin-top: 8px;
            }

            .emca-card-actions button {
                min-height: 34px;
                border-radius: 6px;
                font-size: 0.75rem;
            }
        }

        @media print {
            @page {
                margin: 8mm;
            }

            html,
            body {
                background: #fff !important;
            }

            .main-header,
            .sticky-header,
            .mobile-menu,
            .main-footer,
            .page-title,
            .preloader,
            .back-to-top-wrapper,
            .emca-card-actions,
            [data-emca-region="rsvp"] {
                display: none !important;
            }

            .wow {
                visibility: visible !important;
                opacity: 1 !important;
                animation: none !important;
            }

            .page-wrapper,
            .fix,
            .auto-container,
            .row,
            [class*="col-"] {
                position: static !important;
                overflow: visible !important;
                transform: none !important;
                height: auto !important;
                max-height: none !important;
            }

            .section-padding {
                padding: 0 !important;
            }

            .emca-guest-card,
            .emca-guest-card .jp-card,
            .emca-guest-card .jp-card * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .emca-guest-card {
                position: static !important;
                left: auto !important;
                top: auto !important;
                width: 148mm !important;
                max-width: none !important;
            }

            .emca-guest-card .jp-card {
                width: 148mm !important;
                max-width: none !important;
                margin: 0;
                box-shadow: none;
                break-inside: avoid;
            }
        }
    </style>
@endpush
