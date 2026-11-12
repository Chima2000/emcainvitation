@php
    $useCardUrl = auth()->check()
        ? route('events.index', ['new' => 1, 'template' => $template->id])
        : route('register');
@endphp
<div class="blog-block wow fadeInUp" data-wow-delay="{{ $delay ?? 200 }}ms">
    <div class="thumb">
        <div class="emca-swatch" style="background: {{ $template->paper }}; color: {{ $template->ink }}; --swatch-accent: {{ $template->accent }};">
            <span class="emca-swatch-kicker" style="color: {{ $template->accent }};">{{ $template->category?->label() }}</span>
            <span class="emca-swatch-title">{{ $template->name }}</span>
            <span class="emca-swatch-kicker">{{ __('You are invited') }}</span>
        </div>
    </div>
    <div class="content">
        <a href="{{ route('templates.index', ['category' => $template->category?->slug]) }}" class="tag">{{ $template->category?->label() }}</a>
        <h3 class="h4 entry-title"><a href="{{ $useCardUrl }}">{{ $template->name }}</a></h3>
        <div class="mascot-post-excerpt">{{ $template->description }}</div>
        <span class="emca-price mt-3">TZS {{ number_format($template->price) }}</span>
        <a href="{{ $useCardUrl }}" class="link-btn mt-0">{{ __('Use this card') }} <i class="fa fa-arrow-right"></i></a>
    </div>
</div>
