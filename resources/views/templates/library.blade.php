@extends('layouts.dashboard')

@section('title', __('Templates'))
@section('heading', __('Templates'))
@section('subheading', __('Choose a card, then change its colours, font, layout, picture and words in Design the card.'))

@section('content')
    <div class="card emca-filter-card">
        <div class="card-body py-3">
            <nav class="emca-filter-pills" aria-label="{{ __('Templates') }}">
                <a href="{{ route('template-library.index') }}" @class(['active' => ! $activeCategory])>{{ __('All') }}</a>
                @foreach ($categories as $category)
                    <a href="{{ route('template-library.index', ['category' => $category->slug]) }}" @class(['active' => $activeCategory?->slug === $category->slug])>{{ $category->label() }}</a>
                @endforeach
            </nav>
        </div>
    </div>

    <div class="row">
        @forelse ($templates as $template)
            <div class="col-xxl-3 col-lg-4 col-sm-6">
                <div class="card emca-template-card">
                    <div class="p-3 pb-0">
                        <x-invitation-card
                            compact
                            :design="$template->design()"
                            :kicker="$template->category?->label()"
                            :title="$template->name"
                            :hosts="__('Your names here')"
                            :headline="$template->default_headline"
                            :when="__('Your date and time')"
                            :venue="__('Your venue')"
                        />
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                            <span class="badge bg-primary-subtle text-primary">{{ $template->category?->label() }}</span>
                            <strong class="text-nowrap">{{ $template->price > 0 ? 'TZS '.number_format($template->price) : __('Free') }}</strong>
                        </div>
                        <h2 class="h6 mb-1">{{ $template->name }}</h2>
                        <p class="text-body text-sm mb-3">{{ \Illuminate\Support\Str::limit($template->description, 110) }}</p>
                        <x-new-event variant="primary" class="w-100 mt-auto" :data-emca-fill="json_encode(array_filter(['invitation_template_id' => $template->id, 'type' => $template->category?->is_active ? $template->category->slug : null]))">
                            {{ __('Use this card') }}
                        </x-new-event>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body"><div class="emca-empty">{{ __('No templates in this category yet.') }}</div></div></div>
            </div>
        @endforelse
    </div>
@endsection
