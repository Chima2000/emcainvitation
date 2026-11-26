<div @class(['col-xxl-3 col-lg-4 col-sm-6']) @if (! empty($folded)) data-fold-item hidden @endif>
    <div class="card emca-template-card">
        <div class="p-3 pb-0">
            @php
                $face = $template->galleryFace();
            @endphp
            <x-invitation-card
                compact
                :design="$template->design()"
                :kicker="$template->category?->label()"
                :title="$face['title'] ?? $template->name"
                :hosts="$face['hosts'] ?? __('Your names here')"
                :guest-name="$face['guest'] ?? ($face ? null : __('A guest\'s name'))"
                :headline="$template->shownHeadline()"
                :when="$face['when'] ?? __('Your date and time')"
                :venue="$face['venue'] ?? __('Your venue')"
            />
        </div>
        <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <span class="badge bg-primary-subtle text-primary">{{ $template->category?->label() }}</span>
                @if ($template->host_only)
                    <strong class="text-nowrap">{{ __('For hosts') }}</strong>
                @endif
            </div>
            <h2 class="h6 mb-1">{{ $template->name }}</h2>
            <p class="text-body text-sm mb-3">{{ \Illuminate\Support\Str::limit($template->description, 110) }}</p>
            @if (auth()->user()->isLead() && $template->host_only)
                <a href="{{ route('become-host.create') }}" class="btn btn-primary w-100 mt-auto">{{ __('Become a host') }}</a>
            @else
                <x-new-event variant="primary" class="w-100 mt-auto" :data-emca-fill="json_encode(array_filter(['invitation_template_id' => $template->id, 'type' => $template->category?->is_active ? $template->category->slug : null]))">
                    {{ __('Use this card') }}
                </x-new-event>
            @endif
        </div>
    </div>
</div>
