<div class="d-flex align-items-center justify-content-between gap-2">
    <button
        type="button"
        class="btn btn-sm btn-light emca-guest-toggle"
        data-guest-list-toggle
        data-guest-list-show="{{ $show }}"
        data-guest-list-hide="{{ $hide }}"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        aria-controls="{{ $id }}"
    >
        <span data-guest-list-label>{{ $open ? $hide : $show }}</span>
    </button>
    <span class="text-body">({{ $count }})</span>
</div>
