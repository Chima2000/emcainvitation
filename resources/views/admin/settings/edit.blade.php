@extends('layouts.admin')

@section('title', __('SMS & security'))
@section('heading', __('SMS & security'))

@section('breadcrumb')
    <li class="breadcrumb-item">{{ __('System') }}</li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('SMS & security') }}</li>
@endsection

@php
    $testErrors = $errors->getBag('testSms');
    $placeholders = ['{code}' => __('the 6-digit code'), '{minutes}' => __('minutes before it expires'), '{app}' => config('app.name'), '{name}' => __('the person\'s name')];
@endphp

@section('content')
    <div class="row">
        <div class="col-xl-8">
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">{{ __('SMS gateway') }}</h6>
                            <small class="text-body">{{ __('Used for registration codes and password reset codes.') }}</small>
                        </div>
                        @if ($sms['configured'])
                            <span class="badge bg-success-subtle text-success">{{ __('Ready to send') }}</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">{{ __('Not set up') }}</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="sms_base_url">{{ __('Gateway URL') }}</label>
                                <input type="url" class="form-control @error('sms_base_url') is-invalid @enderror" id="sms_base_url" name="sms_base_url" value="{{ old('sms_base_url', $sms['base_url']) }}" placeholder="https://messaging-service.co.tz/link/sms/v1/text/single" required>
                                @error('sms_base_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sms_username">{{ __('Username') }}</label>
                                <input type="text" class="form-control @error('sms_username') is-invalid @enderror" id="sms_username" name="sms_username" value="{{ old('sms_username', $sms['username']) }}" autocomplete="off" required>
                                @error('sms_username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sms_password">{{ __('Password') }}</label>
                                <input type="password" class="form-control @error('sms_password') is-invalid @enderror" id="sms_password" name="sms_password" autocomplete="new-password" placeholder="{{ $sms['has_password'] ? '••••••••' : '' }}">
                                <small class="form-text">{{ $sms['has_password'] ? __('Leave empty to keep the saved password.') : __('No password saved yet.') }}</small>
                                @error('sms_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="sms_sender_id">{{ __('Sender ID') }}</label>
                                <input type="text" class="form-control @error('sms_sender_id') is-invalid @enderror" id="sms_sender_id" name="sms_sender_id" value="{{ old('sms_sender_id', $sms['sender_id']) }}" maxlength="11" required>
                                <small class="form-text">{{ __('The name people see as the sender. Up to 11 letters or numbers, and it must be registered with your SMS provider.') }}</small>
                                @error('sms_sender_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div>
                                    <input type="hidden" name="sms_verify_ssl" value="0">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="sms_verify_ssl" name="sms_verify_ssl" value="1" @checked(old('sms_verify_ssl', $sms['verify_ssl']))>
                                        <label class="form-check-label" for="sms_verify_ssl">{{ __('Check the gateway SSL certificate') }}</label>
                                    </div>
                                    <small class="form-text">{{ __('Keep this on unless your provider tells you otherwise.') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-1">{{ __('Registration security') }}</h6>
                        <small class="text-body">{{ __('Phone verification sends a code by SMS before a new account is created.') }}</small>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="registration_verify_phone" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="registration_verify_phone" name="registration_verify_phone" value="1" @checked(old('registration_verify_phone', $verifyPhone))>
                            <label class="form-check-label fw-semibold" for="registration_verify_phone">{{ __('Verify the phone number by SMS code when a host registers') }}</label>
                        </div>
                        <small class="form-text d-block mb-3">{{ __('Switch this off and hosts register straight away without a code. Everything else keeps working.') }}</small>
                        @if ($verifyPhone && ! $sms['configured'])
                            <div class="alert alert-warning py-2 small">{{ __('The SMS gateway is not set up, so registration currently skips the code. Finish the gateway details above to turn it on.') }}</div>
                        @endif
                        <div class="row">
                            <div class="col-md-5">
                                <label class="form-label" for="code_lifetime">{{ __('Code expires after') }}</label>
                                <div class="input-group">
                                    <input type="number" min="2" max="60" class="form-control @error('code_lifetime') is-invalid @enderror" id="code_lifetime" name="code_lifetime" value="{{ old('code_lifetime', $codeLifetime) }}" required>
                                    <span class="input-group-text">{{ __('minutes') }}</span>
                                    @error('code_lifetime')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-1">{{ __('SMS messages') }}</h6>
                        <small class="text-body">{{ __('You can use these words in curly brackets and they are replaced when the SMS is sent:') }}</small>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach ($placeholders as $placeholder => $meaning)
                                <span class="badge bg-light text-dark border"><code>{{ $placeholder }}</code> {{ $meaning }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="registration_message">{{ __('Registration code message') }}</label>
                            <textarea class="form-control @error('registration_message') is-invalid @enderror" id="registration_message" name="registration_message" rows="3" maxlength="320" required>{{ old('registration_message', $registrationMessage) }}</textarea>
                            @error('registration_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="password_reset_message">{{ __('Password reset code message') }}</label>
                            <textarea class="form-control @error('password_reset_message') is-invalid @enderror" id="password_reset_message" name="password_reset_message" rows="3" maxlength="320" required>{{ old('password_reset_message', $passwordResetMessage) }}</textarea>
                            @error('password_reset_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <x-button>{{ __('Save settings') }}</x-button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-xl-4">
            <form method="POST" action="{{ route('admin.settings.test-sms') }}" class="card">
                @csrf
                <div class="card-header">
                    <h6 class="card-title mb-1">{{ __('Send a test SMS') }}</h6>
                    <small class="text-body">{{ __('Save the settings first, then send a test to make sure delivery works.') }}</small>
                </div>
                <div class="card-body">
                    <label class="form-label" for="test_phone">{{ __('Phone number') }}</label>
                    <x-phone-input id="test_phone" name="test_phone" value="{{ old('test_phone', $adminPhone) }}" placeholder="0692586046" inputmode="tel" required />
                    @if ($testErrors->has('test_phone'))
                        <div class="invalid-feedback d-block">{{ $testErrors->first('test_phone') }}</div>
                    @endif
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-outline-primary waves-effect" @disabled(! $sms['configured'])>
                        <i class="fi fi-rr-paper-plane me-1"></i> {{ __('Send test SMS') }}
                    </button>
                </div>
            </form>
            <div class="card" id="message-queue">
                <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">{{ __('Message queue') }}</h6>
                        <small class="text-body">{{ __('SMS goes out in the background so nobody waits for the gateway.') }}</small>
                    </div>
                    @if ($queue['runsInline'])
                        <span class="badge bg-warning-subtle text-warning">{{ __('Not in background') }}</span>
                    @elseif ($queue['looksStuck'])
                        <span class="badge bg-danger-subtle text-danger">{{ __('Needs attention') }}</span>
                    @else
                        <span class="badge bg-success-subtle text-success">{{ __('Running') }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($queue['runsInline'])
                        <div class="alert alert-warning py-2 small">{{ __('The queue connection is ":connection", so every SMS is sent while the visitor waits. On the live server set QUEUE_CONNECTION=database and start a worker.', ['connection' => $queue['connection']]) }}</div>
                    @elseif (! $queue['reachable'])
                        <div class="alert alert-danger py-2 small">{{ __('The queue could not be read. Check the queue connection settings and the logs.') }}</div>
                    @elseif ($queue['looksStuck'])
                        <div class="alert alert-danger py-2 small">{{ __('Messages have been waiting for :time. No worker seems to be running; start one with the command below.', ['time' => \Carbon\CarbonInterval::seconds($queue['oldestWaitSeconds'])->cascade()->forHumans(['short' => true])]) }}</div>
                    @endif

                    @unless ($queue['runsInline'])
                        <ul class="list-unstyled mb-3">
                            @foreach ($queue['queues'] as $line)
                                <li class="d-flex justify-content-between py-1 border-bottom">
                                    <span>{{ $line['label'] }}</span>
                                    <span class="fw-semibold">{{ trans_choice(':count waiting|:count waiting', $line['waiting'], ['count' => number_format($line['waiting'])]) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endunless

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ __('Failed jobs') }}</span>
                        @if ($queue['failed'] > 0)
                            <form method="POST" action="{{ route('admin.settings.queue-flush') }}" class="d-flex align-items-center gap-2" data-emca-confirm="{{ __('Clear the failed jobs list? Failed guest messages were already refunded to the host.') }}">
                                @csrf
                                @method('DELETE')
                                <span class="badge bg-danger-subtle text-danger">{{ number_format($queue['failed']) }}</span>
                                <button type="submit" class="btn btn-sm btn-light text-danger">{{ __('Clear') }}</button>
                            </form>
                        @else
                            <span class="badge bg-success-subtle text-success">0</span>
                        @endif
                    </div>

                    <label class="form-label small text-body mb-1">{{ __('Worker command (Supervisor)') }}</label>
                    <pre class="bg-light border rounded p-2 small mb-2 text-wrap"><code>{{ $queue['workerCommand'] }}</code></pre>
                    <label class="form-label small text-body mb-1">{{ __('Cron entry (every minute)') }}</label>
                    <pre class="bg-light border rounded p-2 small mb-2 text-wrap"><code>{{ '* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1' }}</code></pre>
                    <small class="form-text d-block">
                        {{ $worksFromScheduler
                            ? __('QUEUE_WORK_FROM_SCHEDULER is on: the cron entry also sends the queued messages, so no Supervisor worker is needed.')
                            : __('No Supervisor on your hosting? Set QUEUE_WORK_FROM_SCHEDULER=true and the cron entry will send the queued messages too.') }}
                    </small>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">{{ __('Where SMS is used') }}</h6>
                    <ul class="emca-check-list mb-0">
                        <li>{{ __('A code to confirm the phone number when a host registers.') }}</li>
                        <li>{{ __('A code to set a new password from the sign-in page.') }}</li>
                        <li>{{ __('Guest invitations, reminders and thank-you messages, sent in the background with automatic retries.') }}</li>
                        <li>{{ __('The gateway password is stored encrypted and is never shown again.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
