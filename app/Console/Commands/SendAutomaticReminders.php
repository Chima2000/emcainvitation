<?php

namespace App\Console\Commands;

use App\Actions\DispatchGuestMessages;
use App\EventStatus;
use App\MessageChannel;
use App\MessagePurpose;
use App\Models\Event;
use App\Support\SystemSettings;
use Illuminate\Console\Command;

class SendAutomaticReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Prepare one reminder for guests who have not replied';

    public function handle(DispatchGuestMessages $dispatchGuestMessages): int
    {
        $channel = SystemSettings::smsIsConfigured() ? MessageChannel::Sms : MessageChannel::Whatsapp;

        Event::query()
            ->where('auto_reminder', true)
            ->whereNull('auto_reminder_sent_at')
            ->where('status', EventStatus::Published)
            ->where('event_date', '>', now())
            ->with('user')
            ->orderBy('id')
            ->each(function (Event $event) use ($dispatchGuestMessages, $channel): void {
                if ($event->user?->isLead() || $event->event_date->greaterThan(now()->addDays($event->reminder_days))) {
                    return;
                }

                $result = $dispatchGuestMessages->handle($event, MessagePurpose::Reminder, $channel);

                if ($result['paused'] > 0) {
                    return;
                }

                $event->forceFill(['auto_reminder_sent_at' => now()])->save();
            });

        return self::SUCCESS;
    }
}
