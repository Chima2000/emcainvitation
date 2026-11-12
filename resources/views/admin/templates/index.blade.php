@extends('layouts.admin')

@section('title', __('Card templates'))
@section('heading', __('Card templates'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Catalog') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Card templates') }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.event-categories.index') }}" class="btn btn-light waves-effect">
        <i class="fi fi-rr-apps me-1"></i> {{ __('Manage categories') }}
    </a>
    <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#templateModal"
        data-emca-action="{{ route('admin.templates.store') }}"
        data-emca-fill="{{ json_encode(['_method' => 'POST', 'modal_title' => __('New card template'), 'name' => '', 'category' => $defaultCategory, 'price' => 0, 'paper' => '#faf7f7', 'ink' => '#16171a', 'accent' => '#940000', 'font' => 'playfair', 'layout' => 'classic', 'default_headline' => '', 'default_message' => '', 'remove_background' => false, 'motif' => '', 'description' => '']) }}">
        <i class="fi fi-rr-plus me-1"></i> {{ __('New template') }}
    </button>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="emca-filter-pills">
                <a href="{{ route('admin.templates.index') }}" @class(['active' => ! $category])>{{ __('All') }} <span class="emca-pill-count">{{ $totalTemplates }}</span></a>
                @foreach ($filterCategories as $categoryOption)
                    <a href="{{ route('admin.templates.index', ['category' => $categoryOption->slug]) }}" @class(['active' => $category?->slug === $categoryOption->slug, 'is-muted' => ! $categoryOption->is_active]) @if (! $categoryOption->is_active) title="{{ __('Switched off for hosts') }}" @endif>
                        <i class="fa-solid {{ $categoryOption->iconClass() }} me-1"></i>{{ $categoryOption->label() }}
                        <span class="emca-pill-count">{{ $templateCounts->get($categoryOption->slug, 0) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row">
        @forelse ($templates as $template)
            <div class="col-xxl-3 col-lg-4 col-sm-6">
                <div class="card h-100">
                    <div class="p-3 pb-0">
                        <x-invitation-card
                            compact
                            :design="$template->design()"
                            :kicker="$template->category?->label()"
                            :title="$template->name"
                            :hosts="__('Host names')"
                            :headline="$template->default_headline"
                            :when="__('Saturday, 12 December')"
                            :venue="$template->motif"
                        />
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <div class="d-flex gap-1 align-items-center">
                                    @foreach (['paper', 'ink', 'accent'] as $swatch)
                                        <span class="emca-swatch-dot" style="background: {{ $template->{$swatch} }};" title="{{ $template->{$swatch} }}"></span>
                                    @endforeach
                                    <small class="text-body ms-1">{{ $template->font->label() }} · {{ $template->layout->label() }}</small>
                                </div>
                            <strong>{{ $template->price > 0 ? 'TZS '.number_format($template->price) : __('Free') }}</strong>
                        </div>
                        <p class="text-body text-sm mb-3">{{ \Illuminate\Support\Str::limit($template->description, 110) }}</p>
                        <div class="mt-auto">
                            <small class="d-block text-body mb-2">{{ trans_choice(':count event uses it|:count events use it', $template->events_count) }}</small>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light flex-fill" data-bs-toggle="modal" data-bs-target="#templateModal"
                                    data-emca-action="{{ route('admin.templates.update', $template) }}"
                                    data-emca-fill="{{ json_encode(['_method' => 'PUT', 'modal_title' => __('Edit :name', ['name' => $template->name]), 'name' => $template->name, 'category' => $template->category?->slug, 'price' => $template->price, 'paper' => $template->paper, 'ink' => $template->ink, 'accent' => $template->accent, 'font' => $template->font->value, 'layout' => $template->layout->value, 'default_headline' => $template->default_headline, 'default_message' => $template->default_message, 'remove_background' => false, 'motif' => $template->motif, 'description' => $template->description]) }}">
                                    <i class="fi fi-rr-edit me-1"></i> {{ __('Edit') }}
                                </button>
                                <form method="POST" action="{{ route('admin.templates.destroy', $template) }}" data-emca-confirm="{{ __('Delete :name? Events that use it switch to the default design.', ['name' => $template->name]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger" aria-label="{{ __('Delete') }}"><i class="fi fi-rr-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card"><div class="card-body"><div class="emca-empty">{{ __('No card templates in this category yet.') }}</div></div></div>
            </div>
        @endforelse
    </div>
    <div class="mt-3">{{ $templates->links('pagination::bootstrap-5') }}</div>
@endsection

@push('modals')
    @php
        $templateErrors = $errors->getBag('template');
        $templateAction = \App\Support\AdminForm::restoredAction(route('admin.templates.store'));
    @endphp
    <div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true" data-emca-open="{{ $templateErrors->any() ? '1' : '0' }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ $templateAction }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
                    <input type="hidden" name="_form_action" value="{{ $templateAction }}">
                    <input type="hidden" name="modal_title" value="{{ old('modal_title', __('New card template')) }}">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="templateModalLabel" data-emca-text="modal_title">{{ old('modal_title', __('New card template')) }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="template-name">{{ __('Name') }}</label>
                                <input type="text" class="form-control @if ($templateErrors->has('name')) is-invalid @endif" id="template-name" name="name" value="{{ old('name') }}" maxlength="80" required>
                                @if ($templateErrors->has('name'))<div class="invalid-feedback">{{ $templateErrors->first('name') }}</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="template-category">{{ __('Event type') }}</label>
                                <select class="form-select @if ($templateErrors->has('category')) is-invalid @endif" id="template-category" name="category">
                                    @foreach ($categories as $categoryOption)
                                        <option value="{{ $categoryOption->slug }}" @selected(old('category', $defaultCategory) === $categoryOption->slug)>{{ $categoryOption->label() }}@if (! $categoryOption->is_active) · {{ __('switched off') }}@endif</option>
                                    @endforeach
                                </select>
                                @if ($templateErrors->has('category'))<div class="invalid-feedback">{{ $templateErrors->first('category') }}</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="template-motif">{{ __('Motif') }}</label>
                                <input type="text" class="form-control @if ($templateErrors->has('motif')) is-invalid @endif" id="template-motif" name="motif" value="{{ old('motif') }}" maxlength="60" placeholder="{{ __('For example: gold leaves') }}" required>
                                @if ($templateErrors->has('motif'))<div class="invalid-feedback">{{ $templateErrors->first('motif') }}</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="template-price">{{ __('Price (TZS)') }}</label>
                                <input type="text" inputmode="numeric" autocomplete="off" data-emca-money class="form-control @if ($templateErrors->has('price')) is-invalid @endif" id="template-price" name="price" value="{{ old('price', 0) }}" required>
                                @if ($templateErrors->has('price'))<div class="invalid-feedback">{{ $templateErrors->first('price') }}</div>@endif
                            </div>
                            @foreach (['paper' => [__('Paper colour'), '#faf7f7'], 'ink' => [__('Text colour'), '#16171a'], 'accent' => [__('Accent colour'), '#940000']] as $colorField => [$colorLabel, $colorDefault])
                                <div class="col-md-4">
                                    <label class="form-label" for="template-{{ $colorField }}">{{ $colorLabel }}</label>
                                    <input type="color" class="form-control form-control-color w-100 @if ($templateErrors->has($colorField)) is-invalid @endif" id="template-{{ $colorField }}" name="{{ $colorField }}" value="{{ old($colorField, $colorDefault) }}" required>
                                    @if ($templateErrors->has($colorField))<div class="invalid-feedback">{{ $templateErrors->first($colorField) }}</div>@endif
                                </div>
                            @endforeach
                            <div class="col-md-6">
                                <label class="form-label" for="template-font">{{ __('Font for names and headline') }}</label>
                                <select class="form-select @if ($templateErrors->has('font')) is-invalid @endif" id="template-font" name="font">
                                    @foreach ($fonts as $font)
                                        <option value="{{ $font->value }}" style="font-family: {{ $font->family() }};" @selected(old('font', 'playfair') === $font->value)>{{ $font->label() }}</option>
                                    @endforeach
                                </select>
                                @if ($templateErrors->has('font'))<div class="invalid-feedback">{{ $templateErrors->first('font') }}</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="template-layout">{{ __('Layout') }}</label>
                                <select class="form-select @if ($templateErrors->has('layout')) is-invalid @endif" id="template-layout" name="layout">
                                    @foreach ($layouts as $layout)
                                        <option value="{{ $layout->value }}" @selected(old('layout', 'classic') === $layout->value)>{{ $layout->label() }} · {{ $layout->description() }}</option>
                                    @endforeach
                                </select>
                                @if ($templateErrors->has('layout'))<div class="invalid-feedback">{{ $templateErrors->first('layout') }}</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="template-background">{{ __('Background picture (optional)') }}</label>
                                <input type="file" class="form-control @if ($templateErrors->has('background')) is-invalid @endif" id="template-background" name="background" accept="image/jpeg,image/png,image/webp">
                                @if ($templateErrors->has('background'))<div class="invalid-feedback">{{ $templateErrors->first('background') }}</div>@endif
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="template-remove-background" name="remove_background" value="1">
                                    <label class="form-check-label" for="template-remove-background">{{ __('Remove the current picture') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="template-default-headline">{{ __('Default headline') }}</label>
                                <input type="text" class="form-control @if ($templateErrors->has('default_headline')) is-invalid @endif" id="template-default-headline" name="default_headline" value="{{ old('default_headline') }}" maxlength="160" placeholder="{{ __('You are invited') }}">
                                @if ($templateErrors->has('default_headline'))<div class="invalid-feedback">{{ $templateErrors->first('default_headline') }}</div>@endif
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="template-default-message">{{ __('Default message on the card') }}</label>
                                <textarea class="form-control @if ($templateErrors->has('default_message')) is-invalid @endif" id="template-default-message" name="default_message" rows="2" maxlength="2000">{{ old('default_message') }}</textarea>
                                <small class="text-body">{{ __('Hosts can change every part of the design and wording for their own event.') }}</small>
                                @if ($templateErrors->has('default_message'))<div class="invalid-feedback">{{ $templateErrors->first('default_message') }}</div>@endif
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="template-description">{{ __('Description') }}</label>
                                <textarea class="form-control @if ($templateErrors->has('description')) is-invalid @endif" id="template-description" name="description" rows="3" maxlength="500" required>{{ old('description') }}</textarea>
                                @if ($templateErrors->has('description'))<div class="invalid-feedback">{{ $templateErrors->first('description') }}</div>@endif
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <x-button>{{ __('Save template') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
