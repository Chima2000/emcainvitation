@php
    $openCreateEventModal = request()->boolean('new') || $errors->getBag('createEvent')->any();
    $previewTemplateId = old('invitation_template_id', $selectedTemplate ?? '');
    $previewTemplate = $templates->firstWhere('id', (int) $previewTemplateId);
    $previewDesign = $previewTemplate?->design() ?? \App\Models\InvitationTemplate::defaultDesign();
    $previewType = $types->firstWhere('slug', old('type')) ?? $types->first();
    $previewOrganization = (bool) old('host_name_is_organization', false);
    $previewHost = $previewOrganization ? old('organization_name', '') : auth()->user()->name;
    $previewPartner = old('partner_name');
    $previewHosts = $previewHost.($previewPartner ? ' & '.$previewPartner : '');
    $previewDressCodeColors = old('dress_code_colors', []);
    $previewDressCodeColors = is_array($previewDressCodeColors) ? $previewDressCodeColors : [];
    $previewTemplates = $templates->mapWithKeys(fn ($template) => [(string) $template->id => [
        'kicker' => $template->category->label(),
        'paper' => $template->paper,
        'ink' => $template->ink,
        'accent' => $template->accent,
        'fontFamily' => $template->font->family(),
        'layout' => $template->layout->value,
        'backgroundUrl' => $template->backgroundUrl(),
        'headline' => $template->default_headline,
        'message' => $template->default_message,
    ]])->all();
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
            <form method="POST" action="{{ route('events.store') }}" data-event-form data-event-preview-form
                data-preview-templates="{{ json_encode((object) $previewTemplates, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                data-preview-default="{{ json_encode($previewDefaultDesign, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                data-preview-date-fallback="{{ __('Date and time') }}"
                data-preview-title-fallback="{{ __('Event title') }}"
                data-preview-venue-fallback="{{ __('Venue') }}"
                data-preview-invite-headline="{{ __('You are invited') }}"
                data-preview-dress-prefix="{{ __('Dress') }}"
                data-event-field-prefix="create-event-">
                @csrf
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            @include('events.partials.fields', [
                                'fieldId' => 'create-event-',
                                'errorBag' => 'createEvent',
                            ])
                        </div>
                        <aside class="col-lg-5">
                            <div class="position-sticky top-0">
                                <h2 class="h6 mb-3">{{ __('Live invitation preview') }}</h2>
                                <x-invitation-card
                                    :design="$previewDesign"
                                    :kicker="$previewTemplate?->category->label() ?? $previewType?->label() ?? __('Invitation')"
                                    :title="old('title', __('Event title'))"
                                    :hosts="$previewHosts"
                                    :headline="old('headline', $previewTemplate?->default_headline ?? __('You are invited'))"
                                    :when="__('Date and time')"
                                    :venue="old('venue', __('Venue'))"
                                    :message="old('message', $previewTemplate?->default_message)"
                                    :dress-code="old('dress_code')"
                                    :dress-code-colors="$previewDressCodeColors"
                                    compact
                                    class="emca-create-event-preview"
                                    data-preview-card
                                />
                                <p class="small text-body mt-3 mb-0">{{ __('Your invitation preview updates as you fill in the form.') }}</p>
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

            if (modal?.dataset.emcaOpen === '1' && window.bootstrap?.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modal).show();
            }
        });
    </script>
@endpushOnce
