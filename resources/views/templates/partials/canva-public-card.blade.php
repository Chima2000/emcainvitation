<div class="blog-block">
    <div class="thumb">
        <div class="emca-swatch" style="background: linear-gradient(160deg, #7d2ae8, #00c4cc); color: #ffffff; --swatch-accent: #ffffff;">
            <span class="emca-swatch-kicker" style="color: #ffffff;">Canva</span>
            <span class="emca-swatch-title">{{ __('Design in Canva') }}</span>
        </div>
    </div>
    <div class="content">
        <span class="tag">Canva</span>
        <h3 class="h4 entry-title">{{ __('Design in Canva') }}</h3>
        <div class="mascot-post-excerpt">{{ __('You will be able to design this card in Canva and bring it back here. That connection is not open yet.') }}</div>
        <a href="{{ auth()->check() ? route('template-library.index') : route('login', ['next' => 'templates']) }}" class="emca-canva-action">{{ __('Open Canva') }}</a>
    </div>
</div>
