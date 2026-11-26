@props([
    'categories',
    'active' => null,
    'routeName',
    'query' => [],
    'counts' => null,
    'total' => null,
])

@php
    $activeSlug = $active instanceof \App\Models\EventCategory ? $active->slug : $active;
    $groups = \App\Models\EventCategory::grouped($categories);
@endphp

<label class="emca-category-choice">
    <span class="emca-category-choice__label">{{ __('Event type') }}</span>
    <select class="form-select emca-category-choice__select" aria-label="{{ __('Event type') }}" onchange="if (this.value) window.location.href = this.value">
        <option value="{{ route($routeName, $query) }}#template-cards" @selected($activeSlug === null)>
            {{ __('All') }}{{ $total !== null ? ' ('.$total.')' : '' }}
        </option>
        @foreach ($groups as $label => $group)
            <optgroup label="{{ $label }}">
                @foreach ($group as $categoryOption)
                    <option value="{{ route($routeName, array_filter([...$query, 'category' => $categoryOption->slug])) }}#template-cards" @selected($activeSlug === $categoryOption->slug)>
                        {{ $categoryOption->label() }}{{ $categoryOption->is_active ? '' : ' · '.__('switched off') }}{{ $counts !== null ? ' ('.$counts->get($categoryOption->slug, 0).')' : '' }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
</label>
