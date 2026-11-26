@php($templateLocked = auth()->user()->isLead() && $template->host_only)
<div class="col-6 col-md-4" data-template-choice data-template-name="{{ strtolower($template->name.' '.($template->description ?? '')) }}">
    <label @class(['d-block border rounded-3 p-2 h-100 emca-design-choice', 'opacity-50' => $templateLocked])>
        <input type="radio" class="visually-hidden" name="invitation_template_id" value="{{ $template->id }}" @disabled($templateLocked) @checked(! $templateLocked && $selectedTemplateId === (string) $template->id)>
        <span class="d-flex flex-column align-items-center justify-content-center rounded-2 border text-center px-2" style="min-height: 92px; background-color: {{ $template->paper }}; color: {{ $template->ink }}; border-color: {{ $template->accent }} !important; @if ($template->backgroundUrl()) background-image: linear-gradient(color-mix(in srgb, {{ $template->paper }} 80%, transparent), color-mix(in srgb, {{ $template->paper }} 80%, transparent)), url('{{ $template->backgroundUrl() }}'); background-size: cover; @endif font-family: {{ $template->font->family() }};">
            <small class="text-uppercase" style="color: {{ $template->accent }}; font-size: .6rem; letter-spacing: .15em;">{{ $template->category->label() }}</small>
            <span class="fw-semibold">{{ $template->name }}</span>
        </span>
        <span class="d-flex justify-content-between gap-1 small mt-2">
            <span class="fw-semibold text-truncate">{{ $template->name }}</span>
            @if ($template->host_only)
                <span class="text-body text-nowrap">{{ __('For hosts') }}</span>
            @endif
        </span>
    </label>
</div>
