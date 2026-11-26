<div class="emca-scanner-panel">
    <div class="emca-scanner-panel-heading">
        <span class="emca-scanner-panel-icon" aria-hidden="true">
            <i class="fi fi-rr-camera"></i>
        </span>
        <div class="emca-scanner-panel-copy">
            <p class="emca-scanner-eyebrow">{{ __('Camera scanner') }}</p>
            <p class="emca-scanner-instruction">{{ __('Center the invitation QR code in the frame.') }}</p>
        </div>
        <span class="emca-scanner-ready">
            <span aria-hidden="true"></span>
            {{ __('Ready') }}
        </span>
    </div>

    <div class="emca-scanner-stage" data-scanner-stage>
        <video data-scanner playsinline muted hidden></video>
        <div class="emca-scanner-placeholder" data-scanner-placeholder>
            <span class="emca-scanner-placeholder-icon" aria-hidden="true">
                <i class="fi fi-rr-qrcode"></i>
            </span>
            <strong>{{ __('Ready to scan') }}</strong>
            <span>{{ __('Camera stays off until you tap Scan.') }}</span>
        </div>
        <div class="emca-scanner-frame" aria-hidden="true">
            <span class="emca-scanner-corner emca-scanner-corner-top-left"></span>
            <span class="emca-scanner-corner emca-scanner-corner-top-right"></span>
            <span class="emca-scanner-corner emca-scanner-corner-bottom-left"></span>
            <span class="emca-scanner-corner emca-scanner-corner-bottom-right"></span>
        </div>
        <span class="emca-scanner-line" data-scanner-line hidden></span>
        <div class="emca-scanner-verdict" data-scanner-verdict hidden>
            <i class="fi" data-scanner-icon aria-hidden="true"></i>
        </div>
    </div>

    <p class="emca-scanner-status" role="status" aria-live="polite" data-scanner-status hidden></p>

    <x-button
        type="button"
        class="emca-scanner-start"
        aria-pressed="false"
        data-scanner-start
        data-scanner-start-label="{{ __('Scan with camera') }}"
        data-scanner-stop-label="{{ __('Stop scanning') }}"
        data-scanner-insecure="{{ __('Open this site with https to use the camera, or type the card number.') }}"
        data-scanner-denied="{{ __('Allow the camera, or type the card number.') }}"
        data-scanner-looking="{{ __('Point the camera at the invitation code.') }}"
    >
        <i class="fi fi-rr-camera" aria-hidden="true"></i>
        <span data-scanner-label>{{ __('Scan with camera') }}</span>
    </x-button>

    <div class="emca-scanner-divider">
        <span>{{ __('Or enter the card number manually') }}</span>
    </div>

    <x-field name="code" label="{{ __('Card number or code') }}">
        <x-input name="code" data-check-in-code value="{{ old('code') }}" autocomplete="off" required />
    </x-field>

    <x-button variant="secondary" class="emca-manual-checkin">{{ __('Check in') }}</x-button>
</div>
