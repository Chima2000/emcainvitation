@extends('layouts.marketing')

@section('title', __('Templates').' | '.config('app.name'))

@section('content')
    @include('layouts.partials.breadcrumb', [
        'title' => __('Templates'),
        'photo' => 'page-titles/templates.jpg',
        'lead' => __('Choose a card, then start an event with that design already selected.'),
    ])

    <section class="blog-section section-padding fix">
        <div class="auto-container">
            <form method="GET" action="{{ route('templates.index') }}" class="d-flex flex-wrap justify-content-center gap-2 mb-4" role="search">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
                @endif
                <input class="form-control" style="max-width: 28rem;" type="search" name="q" value="{{ $search }}" placeholder="{{ __('Search templates') }}" aria-label="{{ __('Search templates') }}">
                <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
            </form>
            <x-category-choice
                :categories="$categories"
                :active="$activeCategory"
                route-name="templates.index"
                :query="array_filter(['q' => $search])"
            />
            @php
                $showCanvaTemplate = $activeCategory === null && ($search === '' || str_contains(mb_strtolower($search), 'canva'));
            @endphp
            <div class="emca-template-fold" id="template-cards">
            <div class="row gx-4 emca-template-fold-shown" id="template-page-more">
                @if ($showCanvaTemplate)
                    <div class="col-xl-4 col-md-6">
                        @include('templates.partials.canva-public-card')
                    </div>
                @endif
                @forelse ($templates as $index => $template)
                    <div class="col-xl-4 col-md-6" @if ($index >= 6) data-fold-item hidden @endif>
                        @include('templates.partials.card', ['template' => $template, 'delay' => (($index % 3) + 1) * 200])
                    </div>
                @empty
                    @unless ($showCanvaTemplate)
                        <div class="col-12">
                            <div class="emca-panel text-center">{{ $search !== '' ? __('No templates match that search.') : __('No templates in this category yet.') }}</div>
                        </div>
                    @endunless
                @endforelse
            </div>
            @if ($templates->count() > 6)
                @include('partials.view-more', ['id' => 'template-page-more', 'open' => false, 'class' => 'emca-fold-bar d-flex justify-content-center my-4'])
            @endif
            </div>
        </div>
    </section>
@endsection
