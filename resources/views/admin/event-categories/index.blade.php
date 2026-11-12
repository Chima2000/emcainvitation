@extends('layouts.admin')

@section('title', __('Event categories'))
@section('heading', __('Event categories'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('Catalog') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Event categories') }}</li>
@endsection

@section('actions')
    <a href="{{ route('admin.templates.index') }}" class="btn btn-light waves-effect">
        <i class="fi fi-rr-picture me-1"></i> {{ __('Card templates') }}
    </a>
    <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#categoryModal"
        data-emca-action="{{ route('admin.event-categories.store') }}"
        data-emca-fill="{{ json_encode(['_method' => 'POST', 'modal_title' => __('New event category'), 'name' => '', 'name_sw' => '', 'icon' => \App\Models\EventCategory::DefaultIcon, 'photo' => \App\Models\EventCategory::DefaultPhoto, 'photo_upload' => '', 'remove_photo' => false, 'sort_order' => ($categories->max('sort_order') ?? 0) + 10, 'is_active' => true]) }}">
        <i class="fi fi-rr-plus me-1"></i> {{ __('New category') }}
    </button>
@endsection

@section('content')
    <div class="alert alert-light border d-flex flex-wrap gap-2 align-items-center">
        <i class="fi fi-rr-info text-primary"></i>
        <span>{{ __('Categories group card templates and describe each event. Switch one off to hide it from hosts without touching the events that already use it.') }}</span>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h2 class="card-title h6 mb-0">{{ trans_choice(':count category|:count categories', $categories->count()) }}</h2>
            <small class="text-body">{{ __(':count switched on for hosts', ['count' => $categories->where('is_active', true)->count()]) }}</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Category') }}</th>
                        <th scope="col">{{ __('Picture') }}</th>
                        <th scope="col" class="text-center">{{ __('Templates') }}</th>
                        <th scope="col" class="text-center">{{ __('Events') }}</th>
                        <th scope="col" class="text-center">{{ __('Order') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col" class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr @class(['opacity-75' => ! $category->is_active])>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="emca-category-icon"><i class="fa-solid {{ $category->iconClass() }}"></i></span>
                                    <div class="min-w-0">
                                        <strong class="d-block">{{ $category->name }}</strong>
                                        <small class="text-body d-block">{{ $category->name_sw ?: __('No Swahili name yet') }}</small>
                                        <code class="text-2xs">{{ $category->slug }}</code>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <img src="{{ $category->photoUrl() }}" alt="" class="emca-category-thumb">
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.templates.index', ['category' => $category->slug]) }}">{{ number_format($category->templates_count) }}</a>
                            </td>
                            <td class="text-center">{{ number_format($category->events_count) }}</td>
                            <td class="text-center">{{ $category->sort_order }}</td>
                            <td>
                                @if ($category->is_active)
                                    <span class="badge bg-success-subtle text-success">{{ __('On') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Off') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#categoryModal"
                                        data-emca-action="{{ route('admin.event-categories.update', $category) }}"
                                        data-emca-fill="{{ json_encode(['_method' => 'PUT', 'modal_title' => __('Edit :name', ['name' => $category->name]), 'name' => $category->name, 'name_sw' => $category->name_sw, 'icon' => $category->iconClass(), 'photo' => $category->photo, 'photo_upload' => '', 'remove_photo' => false, 'sort_order' => $category->sort_order, 'is_active' => $category->is_active]) }}">
                                        <i class="fi fi-rr-edit me-1"></i> {{ __('Edit') }}
                                    </button>
                                    <form method="POST" action="{{ route('admin.event-categories.destroy', $category) }}" data-emca-confirm="{{ $category->events_count > 0 || $category->templates_count > 0 ? __(':name is already in use. It will be switched off for hosts instead of deleted. Continue?', ['name' => $category->name]) : __('Delete :name?', ['name' => $category->name]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger" aria-label="{{ __('Delete') }}"><i class="fi fi-rr-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><div class="emca-empty">{{ __('No event categories yet. Create the first one.') }}</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('modals')
    @php
        $categoryErrors = $errors->getBag('category');
        $categoryAction = \App\Support\AdminForm::restoredAction(route('admin.event-categories.store'));
    @endphp
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true" data-emca-open="{{ $categoryErrors->any() ? '1' : '0' }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ $categoryAction }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
                    <input type="hidden" name="_form_action" value="{{ $categoryAction }}">
                    <input type="hidden" name="modal_title" value="{{ old('modal_title', __('New event category')) }}">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="categoryModalLabel" data-emca-text="modal_title">{{ old('modal_title', __('New event category')) }}</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label" for="category-name">{{ __('Name in English') }}</label>
                                <input type="text" class="form-control @if ($categoryErrors->has('name')) is-invalid @endif" id="category-name" name="name" value="{{ old('name') }}" maxlength="60" placeholder="{{ __('For example: Graduation') }}" required>
                                @if ($categoryErrors->has('name'))<div class="invalid-feedback">{{ $categoryErrors->first('name') }}</div>@endif
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="category-name-sw">{{ __('Name in Swahili') }}</label>
                                <input type="text" class="form-control @if ($categoryErrors->has('name_sw')) is-invalid @endif" id="category-name-sw" name="name_sw" value="{{ old('name_sw') }}" maxlength="60" placeholder="{{ __('For example: Mahafali') }}">
                                @if ($categoryErrors->has('name_sw'))<div class="invalid-feedback">{{ $categoryErrors->first('name_sw') }}</div>@endif
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="category-sort">{{ __('Order') }}</label>
                                <input type="number" class="form-control @if ($categoryErrors->has('sort_order')) is-invalid @endif" id="category-sort" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="999">
                                @if ($categoryErrors->has('sort_order'))<div class="invalid-feedback">{{ $categoryErrors->first('sort_order') }}</div>@endif
                            </div>
                            <div class="col-12">
                                <span class="form-label d-block">{{ __('Icon') }}</span>
                                <div class="emca-icon-picker" role="radiogroup" aria-label="{{ __('Icon') }}">
                                    @foreach ($icons as $icon)
                                        <div class="position-relative">
                                            <input type="radio" name="icon" id="category-icon-{{ $icon }}" value="{{ $icon }}" @checked(old('icon', \App\Models\EventCategory::DefaultIcon) === $icon)>
                                            <label for="category-icon-{{ $icon }}" title="{{ \Illuminate\Support\Str::headline(\Illuminate\Support\Str::after($icon, 'fa-')) }}"><i class="fa-solid {{ $icon }}"></i></label>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($categoryErrors->has('icon'))<div class="invalid-feedback d-block">{{ $categoryErrors->first('icon') }}</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category-photo">{{ __('Stock picture') }}</label>
                                <select class="form-select @if ($categoryErrors->has('photo')) is-invalid @endif" id="category-photo" name="photo">
                                    @foreach ($photos as $photoFile => $photoLabel)
                                        <option value="{{ $photoFile }}" @selected(old('photo', \App\Models\EventCategory::DefaultPhoto) === $photoFile)>{{ $photoLabel }}</option>
                                    @endforeach
                                </select>
                                <small class="text-body">{{ __('Shown on public event pages when no picture is uploaded.') }}</small>
                                @if ($categoryErrors->has('photo'))<div class="invalid-feedback">{{ $categoryErrors->first('photo') }}</div>@endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="category-photo-upload">{{ __('Own picture (optional)') }}</label>
                                <input type="file" class="form-control @if ($categoryErrors->has('photo_upload')) is-invalid @endif" id="category-photo-upload" name="photo_upload" accept="image/jpeg,image/png,image/webp">
                                @if ($categoryErrors->has('photo_upload'))<div class="invalid-feedback">{{ $categoryErrors->first('photo_upload') }}</div>@endif
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="category-remove-photo" name="remove_photo" value="1">
                                    <label class="form-check-label" for="category-remove-photo">{{ __('Remove the uploaded picture') }}</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="category-is-active" name="is_active" value="1" @checked(old('is_active', true))>
                                    <label class="form-check-label" for="category-is-active">{{ __('Show to hosts') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <x-button>{{ __('Save category') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
