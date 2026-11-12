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
            <nav class="emca-filter" aria-label="{{ __('Templates') }}">
                <a @class(['is-active' => ! $activeCategory]) href="{{ route('templates.index') }}">{{ __('All') }}</a>
                @foreach ($categories as $category)
                    <a @class(['is-active' => $activeCategory?->slug === $category->slug]) href="{{ route('templates.index', ['category' => $category->slug]) }}">{{ $category->label() }}</a>
                @endforeach
            </nav>
            <div class="row gx-4">
                @forelse ($templates as $index => $template)
                    <div class="col-xl-4 col-md-6">
                        @include('templates.partials.card', ['template' => $template, 'delay' => (($index % 3) + 1) * 200])
                    </div>
                @empty
                    <div class="col-12">
                        <div class="emca-panel text-center">{{ __('No templates in this category yet.') }}</div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
