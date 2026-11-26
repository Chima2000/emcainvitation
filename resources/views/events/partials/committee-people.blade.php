@php
    $selectedParticipants = array_map('strval', $selectedParticipants ?? []);
    $selectedLeader = (string) ($selectedLeader ?? '');
    $chooseLeader = $chooseLeader ?? true;
    $participantField = $participantField ?? 'participant_ids';
@endphp

<div class="border rounded-3 p-2" style="max-height: 16rem; overflow: auto;">
    @foreach ($guests as $guest)
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-1 {{ $loop->last ? '' : 'border-bottom' }}">
            <div class="form-check mb-0">
                <input
                    class="form-check-input"
                    type="checkbox"
                    name="{{ $participantField }}[]"
                    id="{{ $prefix }}-participant-{{ $guest->id }}"
                    value="{{ $guest->id }}"
                    @checked(in_array((string) $guest->id, $selectedParticipants, true))
                >
                <label class="form-check-label text-break" for="{{ $prefix }}-participant-{{ $guest->id }}">
                    {{ $guest->name }}{{ $guest->phone ? ' · '.$guest->phone : '' }}{{ $guest->gender ? ' · '.$guest->gender->label() : '' }}
                </label>
            </div>
            @if ($chooseLeader)
                <div class="form-check mb-0">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="leader_guest_id"
                        id="{{ $prefix }}-leader-{{ $guest->id }}"
                        value="{{ $guest->id }}"
                        @checked($selectedLeader === (string) $guest->id)
                    >
                    <label class="form-check-label" for="{{ $prefix }}-leader-{{ $guest->id }}">{{ __('Committee leader') }}</label>
                </div>
            @endif
        </div>
    @endforeach
</div>
<p class="form-text mb-0">{{ $chooseLeader ? __('Tick the participants, and tick one committee leader from the same list.') : __('Tick the guests to add to this committee.') }}</p>
@once
    <script>
        const fitCommitteeNames = () => {
            document.querySelectorAll('.emca-fit-name').forEach((input) => {
                if (!(input instanceof HTMLInputElement) || input.dataset.fitNameReady === '1') {
                    return;
                }

                input.dataset.fitNameReady = '1';

                if (window.CSS?.supports('field-sizing', 'content')) {
                    return;
                }

                const fit = () => {
                    input.style.width = `${Math.max(input.value.length, 8) + 1}ch`;
                };

                fit();
                input.addEventListener('input', fit);
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fitCommitteeNames);
        } else {
            fitCommitteeNames();
        }

        document.addEventListener('emca:page-load', fitCommitteeNames);

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-committee-add-toggle]');

            if (!button) {
                return;
            }

            const panel = document.getElementById(button.getAttribute('data-committee-add-toggle'));

            if (!panel) {
                return;
            }

            panel.hidden = !panel.hidden;
            button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
            button.textContent = panel.hidden ? button.dataset.committeeAddLabel : button.dataset.committeeOpenLabel;
        });

        document.addEventListener('change', (event) => {
            const box = event.target;

            if (!(box instanceof HTMLInputElement) || !box.form) {
                return;
            }

            if (box.name === 'leader_guest_id' && box.type === 'checkbox' && box.checked) {
                box.form.querySelectorAll('input[name="leader_guest_id"]').forEach((other) => {
                    if (other !== box) {
                        other.checked = false;
                    }
                });

                const participant = box.form.querySelector(`input[name$="[]"][value="${box.value}"]`);

                if (participant instanceof HTMLInputElement) {
                    participant.checked = true;
                }
            }

            if ((box.name === 'participant_ids[]' || box.name === 'guest_ids[]') && !box.checked) {
                const leader = box.form.querySelector(`input[name="leader_guest_id"][value="${box.value}"]`);

                if (leader instanceof HTMLInputElement) {
                    leader.checked = false;
                }
            }
        });
    </script>
@endonce
