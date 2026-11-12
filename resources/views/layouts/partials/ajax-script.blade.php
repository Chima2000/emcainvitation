<script
    src="{{ asset('admin/assets/js/emca-ajax.js') }}?v={{ filemtime(public_path('admin/assets/js/emca-ajax.js')) }}"
    data-error="{{ __('Something went wrong. Please try again.') }}"
    data-offline="{{ __('We could not reach the server. Check your connection and try again.') }}"
    data-expired="{{ __('Your session expired. Reloading the page…') }}"
    data-throttled="{{ __('Too many attempts. Wait a moment and try again.') }}"
    data-forbidden="{{ __('You are not allowed to do that.') }}"
    data-close="{{ __('Close') }}"
></script>
