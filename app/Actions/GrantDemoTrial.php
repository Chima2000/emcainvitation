<?php

namespace App\Actions;

use App\Exceptions\SmsNotSent;
use App\Mail\DemoTrialInvitation;
use App\MessageChannel;
use App\MessagePurpose;
use App\MessageStatus;
use App\Models\DemoRequest;
use App\Models\OutboundMessage;
use App\Models\User;
use App\PhoneInput;
use App\Support\SmsGateway;
use App\Support\TanzanianPhone;
use App\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class GrantDemoTrial
{
    public function __construct(private SmsGateway $smsGateway) {}

    /**
     * Open or extend a trial and send the client a link by SMS and email.
     *
     * @return array{user: User, url: string, days: int, smsSent: bool, mailSent: bool}
     */
    public function handle(DemoRequest $demo, int $days): array
    {
        $phone = TanzanianPhone::normalize($demo->phone, PhoneInput::Registration);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'days' => __('This phone number cannot receive a trial link.'),
            ]);
        }

        $email = mb_strtolower(trim($demo->email));
        $byPhone = User::query()->where('phone', $phone)->first();
        $byEmail = User::query()->where('email', $email)->first();

        if ($byPhone instanceof User && $byEmail instanceof User && $byPhone->isNot($byEmail)) {
            throw ValidationException::withMessages([
                'days' => __('This phone and email belong to different accounts.'),
            ]);
        }

        $existing = $byPhone ?? $byEmail;

        if ($existing?->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'days' => __('This contact already belongs to an administrator.'),
            ]);
        }

        $granted = DB::transaction(function () use ($demo, $days, $phone, $email, $existing): array {
            $created = ! $existing instanceof User;
            $user = $existing ?? $this->createLead($demo, $phone, $email);
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $startsAt = $locked->subscription_ends_at !== null && $locked->subscription_ends_at->isFuture()
                ? $locked->subscription_ends_at->toImmutable()
                : now()->toImmutable();
            $endsAt = $startsAt->addDays($days);

            $attributes = ['subscription_ends_at' => $endsAt];

            if (! $locked->subscriptions()->exists()) {
                $attributes['role'] = UserRole::Lead;
            }

            $locked->forceFill($attributes)->save();

            $demo->forceFill([
                'user_id' => $locked->id,
                'trial_days' => $days,
                'trial_starts_at' => $startsAt,
                'trial_ends_at' => $endsAt,
                'trial_granted_at' => now(),
                'trial_cancelled_at' => null,
                'must_set_password' => $created || $demo->must_set_password,
            ])->save();

            return ['user' => $locked, 'endsAt' => $endsAt];
        });

        $demo->refresh();
        $url = URL::temporarySignedRoute('trials.show', $granted['endsAt'], $demo);
        $message = __('Your :days-day Jumuika Pass trial is ready: :url', [
            'days' => $days,
            'url' => $url,
        ]);
        $smsFailure = null;

        try {
            $this->smsGateway->deliver($phone, $message);
            $smsSent = true;
        } catch (SmsNotSent $exception) {
            Log::warning('SMS not sent.', [
                'phone' => preg_replace('/\D+/', '', $phone) ?? '',
                'reason' => $exception->getMessage(),
            ]);
            $smsSent = false;
            $smsFailure = $exception->getMessage() === 'The SMS gateway is not set up.'
                ? __('The SMS gateway is not set up.')
                : $exception->getMessage();
        }

        $endsOn = $granted['endsAt']->timezone(config('app.timezone'))->translatedFormat('j F Y');
        $mail = $this->sendMail($demo, $granted['user'], $days, $url, $endsOn);
        $mailSent = $mail['sent'];

        $this->record($demo, MessageChannel::Sms, $phone, $message, $smsSent, $smsFailure);
        $this->record($demo, MessageChannel::Email, $demo->email, __('Your Jumuika Pass trial is ready'), $mailSent, $mail['reason']);

        return [
            'user' => $granted['user'],
            'url' => $url,
            'days' => $days,
            'smsSent' => $smsSent,
            'mailSent' => $mailSent,
        ];
    }

    private function createLead(DemoRequest $demo, string $phone, string $email): User
    {
        $parts = preg_split('/\s+/u', trim($demo->name), 2) ?: [];
        $firstName = $parts[0] ?? $demo->name;
        $lastName = $parts[1] ?? null;

        return User::query()->create([
            'name' => $demo->name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => Str::password(24),
            'role' => UserRole::Lead,
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array{sent: bool, reason: ?string}
     */
    private function sendMail(DemoRequest $demo, User $user, int $days, string $url, string $endsOn): array
    {
        try {
            Mail::to($demo->email)->send(new DemoTrialInvitation($user->name, $days, $url, $endsOn));
        } catch (Throwable $exception) {
            report($exception);

            return ['sent' => false, 'reason' => $exception->getMessage()];
        }

        if (config('mail.default') === 'log') {
            return ['sent' => false, 'reason' => __('Email is only saved in the application log.')];
        }

        return ['sent' => true, 'reason' => null];
    }

    private function record(DemoRequest $demo, MessageChannel $channel, string $address, string $body, bool $delivered, ?string $failureReason): void
    {
        OutboundMessage::query()->create([
            'recipient_name' => $demo->name,
            'recipient_address' => $address,
            'channel' => $channel,
            'purpose' => MessagePurpose::Trial,
            'body' => $body,
            'status' => $delivered ? MessageStatus::Delivered : MessageStatus::Failed,
            'failure_reason' => $delivered ? null : $failureReason,
            'sent_at' => $delivered ? now() : null,
            'cost' => 0,
            'covered_by_package' => false,
        ]);
    }
}
