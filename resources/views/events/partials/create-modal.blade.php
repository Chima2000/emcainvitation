@php
    $openCreateEventModal = request()->boolean('new') || $errors->getBag('createEvent')->any();
    $restoreCreateInput = $errors->getBag('createEvent')->any();
    $previewTemplateId = $restoreCreateInput ? old('invitation_template_id', $selectedTemplate ?? '') : ($selectedTemplate ?? '');
    $previewTemplate = $templates->firstWhere('id', (int) $previewTemplateId);
    $previewDesign = $previewTemplate?->design() ?? \App\Models\InvitationTemplate::defaultDesign();
    $previewType = $types->firstWhere('slug', $restoreCreateInput ? old('type') : null) ?? $types->first();
    $previewOrganization = $restoreCreateInput && (bool) old('host_name_is_organization', false);
    $previewHost = $previewOrganization ? old('organization_name', '') : auth()->user()->name;
    $previewPartner = $restoreCreateInput ? old('partner_name') : null;
    $previewHosts = $previewHost.($previewPartner ? ' & '.$previewPartner : '');
    $previewDressCodeColors = $restoreCreateInput ? old('dress_code_colors', []) : [];
    $previewDressCodeColors = is_array($previewDressCodeColors) ? $previewDressCodeColors : [];
    $previewTemplates = $templates->mapWithKeys(fn ($template) => [(string) $template->id => $template->previewPayload()])->all();
    $defaultDesign = \App\Models\InvitationTemplate::defaultDesign();
    $previewDefaultDesign = [
        'paper' => $defaultDesign['paper'],
        'ink' => $defaultDesign['ink'],
        'accent' => $defaultDesign['accent'],
        'fontFamily' => $defaultDesign['font']->family(),
        'layout' => $defaultDesign['layout']->value,
    ];
@endphp

<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true" @if ($openCreateEventModal) data-emca-open="1" @endif>
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h5 class="modal-title" id="createEventModalLabel">{{ __('New event') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form method="POST" action="{{ route('events.store') }}" autocomplete="off" data-event-form data-event-preview-form
                data-preview-templates="{{ json_encode((object) $previewTemplates, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                data-preview-default="{{ json_encode($previewDefaultDesign, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                data-preview-invite-headline="{{ __('You are invited') }}"
                data-preview-dress-prefix="{{ __('Dress') }}"
                data-preview-bride-family="{{ __('Bride’s family: :name', ['name' => '__VALUE__']) }}"
                data-preview-groom-family="{{ __('Groom’s family: :name', ['name' => '__VALUE__']) }}"
                data-preview-vows="{{ __('Vows at :place', ['place' => '__VALUE__']) }}"
                data-preview-reception="{{ __('Reception at :place', ['place' => '__VALUE__']) }}"
                data-preview-reception-time="{{ __('Reception time: :time', ['time' => '__VALUE__']) }}"
                data-preview-arrive="{{ __('Arrive by :time', ['time' => '__VALUE__']) }}"
                data-event-field-prefix="create-event-">
                @csrf
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            @include('events.partials.fields', [
                                'fieldId' => 'create-event-',
                                'errorBag' => 'createEvent',
                                'event' => null,
                            ])
                        </div>
                        <aside class="col-lg-5">
                            <div class="position-sticky top-0">
                                <h2 class="h6 mb-3">{{ __('Live invitation preview') }}</h2>
                                <x-invitation-card
                                    :design="$previewDesign"
                                    :kicker="$previewTemplate?->category->label() ?? $previewType?->label() ?? __('Invitation')"
                                    :title="$restoreCreateInput ? old('title', '') : ''"
                                    :hosts="$previewHosts"
                                    :headline="$restoreCreateInput ? old('headline', $previewTemplate?->shownHeadline() ?? __('You are invited')) : ($previewTemplate?->shownHeadline() ?? __('You are invited'))"
                                    :when="''"
                                    :venue="$restoreCreateInput ? old('venue', '') : ''"
                                    :message="$restoreCreateInput ? old('message', $previewTemplate?->shownMessage()) : $previewTemplate?->shownMessage()"
                                    :dress-code="$restoreCreateInput ? old('dress_code') : null"
                                    :dress-code-colors="$previewDressCodeColors"
                                    compact
                                    class="emca-create-event-preview"
                                    data-preview-card
                                />
                                <p class="small text-body mt-3 mb-0">{{ __('The title, date, and venue appear here once you type them. Leave the headline or message empty to use the wording from the template.') }}</p>
                            </div>
                        </aside>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light waves-effect waves-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <x-button>{{ __('Create event') }}</x-button>
                </div>
            </form>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('createEventModal');
            const form = modal?.querySelector('form');

            document.addEventListener('click', (event) => {
                if (!form || !event.target.closest('[data-bs-target="#createEventModal"]')) {
                    return;
                }

                form.reset();
                form.querySelector('[data-organization-host-toggle]')?.dispatchEvent(new Event('change'));
                form.dispatchEvent(new Event('input'));
            }, true);

            if (modal?.dataset.emcaOpen === '1' && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    </script>
@endpushOnce
