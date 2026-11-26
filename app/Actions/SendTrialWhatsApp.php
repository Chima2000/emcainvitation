<?php

namespace App\Actions;

use App\MessageChannel;
use App\MessagePurpose;
use App\MessageStatus;
use App\Models\DemoRequest;
use App\Models\OutboundMessage;
use App\PhoneInput;
use App\Support\TanzanianPhone;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class SendTrialWhatsApp
{
    /**
     * Record the trial text and return a WhatsApp link the admin can send.
     */
    public function handle(DemoRequest $demo): string
    {
        if (! $demo->trialIsActive() || $demo->trial_ends_at === null) {
            throw ValidationException::withMessages([
                'days' => __('There is no active trial to send on WhatsApp.'),
            ]);
        }

        $phone = TanzanianPhone::normalize($demo->phone, PhoneInput::Registration);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'days' => __('This phone number cannot receive a trial link.'),
            ]);
        }

        $url = URL::temporarySignedRoute('trials.show', $demo->trial_ends_at, $demo);
        $message = __('Your :days-day Jumuika Pass trial is ready: :url', [
            'days' => $demo->trial_days ?? 0,
            'url' => $url,
        ]);

        OutboundMessage::query()->create([
            'recipient_name' => $demo->name,
            'recipient_address' => $phone,
            'channel' => MessageChannel::Whatsapp,
            'purpose' => MessagePurpose::Trial,
            'body' => $message,
            'status' => MessageStatus::Sent,
            'sent_at' => now(),
            'cost' => 0,
            'covered_by_package' => false,
        ]);

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }
}
