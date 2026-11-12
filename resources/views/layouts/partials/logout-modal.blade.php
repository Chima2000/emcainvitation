<div class="modal fade" id="logoutConfirmationModal" tabindex="-1" aria-labelledby="logoutConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutConfirmationModalLabel">{{ __('Confirm sign out') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                {{ __('Are you sure you want to sign out of your account?') }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Stay signed in') }}</button>
                <form method="POST" action="{{ route('logout') }}" data-emca-native>
                    @csrf
                    <button class="btn btn-danger" type="submit">{{ __('Sign out') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="emcaConfirmModal" tabindex="-1" aria-labelledby="emcaConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emcaConfirmModalLabel">{{ __('Please confirm') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body" data-emca-confirm-message></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-danger" data-emca-confirm-accept>{{ __('Yes, continue') }}</button>
            </div>
        </div>
    </div>
</div>
