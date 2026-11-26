@extends('layouts.dashboard')

@section('title', __('Templates'))
@section('heading', __('Templates'))
@section('subheading', __('Choose a card, then change its colours, font, layout, picture and words in Design the card.'))

@section('content')
    <div class="card emca-filter-card">
        <div class="card-body py-3 d-grid gap-3">
            <form method="GET" action="{{ route('template-library.index') }}" class="d-flex flex-wrap gap-2" role="search">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
                @endif
                <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="{{ __('Search templates') }}" aria-label="{{ __('Search templates') }}">
                <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
            </form>
            <x-category-choice
                :categories="$categories"
                :active="$activeCategory"
                route-name="template-library.index"
                :query="array_filter(['q' => $search])"
            />
        </div>
    </div>

    @php
        $showCanvaTemplate = $activeCategory === null && ($search === '' || str_contains(mb_strtolower($search), 'canva'));
    @endphp
    <div class="emca-template-fold" id="template-cards">
    <div class="row" id="template-library-more">
        @if ($showCanvaTemplate)
            @include('templates.partials.canva-library-card')
        @endif
        @forelse ($templates as $template)
            @include('templates.partials.library-card', ['folded' => $loop->index >= 6])
        @empty
            @unless ($showCanvaTemplate)
                <div class="col-12">
                    <div class="card"><div class="card-body"><div class="emca-empty">{{ $search !== '' ? __('No templates match that search.') : __('No templates in this category yet.') }}</div></div></div>
                </div>
            @endunless
        @endforelse
    </div>
    @if ($templates->count() > 6)
        @include('partials.view-more', ['id' => 'template-library-more', 'open' => false, 'class' => 'emca-fold-bar d-flex justify-content-center my-4'])
    @endif
    </div>
@endsection
