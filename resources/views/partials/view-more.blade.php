<div class="{{ $class ?? 'mt-3' }}">
    <button
        type="button"
        class="btn btn-sm btn-light emca-guest-toggle"
        data-guest-list-toggle
        data-guest-list-show="{{ __('View more') }}"
        data-guest-list-hide="{{ __('Show fewer') }}"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        aria-controls="{{ $id }}"
    >
        <span data-guest-list-label>{{ $open ? __('Show fewer') : __('View more') }}</span>
    </button>
</div>
@once
    <style>
        .emca-template-fold:has([data-guest-list-toggle][aria-expanded="true"]) .emca-fold-bar {
            position: fixed;
            bottom: 1.25rem;
            left: 50%;
            z-index: 40;
            width: auto;
            margin: 0;
            transform: translateX(-50%);
            padding: 0.35rem;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(18, 19, 21, 0.18);
        }
    </style>
    @push('scripts')
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

                    const folded = list.querySelectorAll(':scope > [data-fold-item]');
                    const open = folded.length > 0 ? folded[0].hidden : list.hidden;

                    if (folded.length > 0) {
                        folded.forEach((item) => {
                            item.hidden = !open;
                        });
                    } else {
                        list.hidden = !open;
                    }

                    button.setAttribute('aria-expanded', open ? 'true' : 'false');

                    const label = button.querySelector('[data-guest-list-label]');

                    if (label) {
                        label.textContent = open ? button.dataset.guestListHide : button.dataset.guestListShow;
                    }

                    if (folded.length > 0 && !open) {
                        button.closest('.emca-template-fold')?.scrollIntoView({ block: 'start' });
                    }
                });
            }
        </script>
    @endpush
@endonce
