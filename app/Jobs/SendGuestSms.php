<?php

namespace App\Jobs;

use App\Actions\MarkMessageFailed;
use App\Exceptions\SmsNotSent;
use App\MessageStatus;
use App\Models\OutboundMessage;
use App\QueueName;
use App\Support\SmsGateway;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Middleware\RateLimited;
use Throwable;

/**
 * Delivers one prepared guest message through the SMS gateway. Gateway outages are retried for a few hours,
 * and a message that finally fails gives the host their money or package credit back.
 */
#[Queue(QueueName::Messages)]
#[Timeout(30)]
#[MaxExceptions(5)]
#[Backoff(15, 60, 180, 600)]
#[DeleteWhenMissingModels]
class SendGuestSms implements ShouldQueueAfterCommit
{
    use Queueable;

    public const string RateLimiter = 'sms-gateway';

    public function __construct(public OutboundMessage $message) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(6);
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new RateLimited(self::RateLimiter)];
    }

    public function handle(SmsGateway $smsGateway): void
    {
        if ($this->message->status !== MessageStatus::Queued) {
            return;
        }

        $phone = $this->message->guest?->internationalPhone();

        try {
            if ($phone === null) {
                throw SmsNotSent::permanently('The guest has no phone number.');
            }

            $smsGateway->deliver($phone, $this->message->body);
        } catch (SmsNotSent $exception) {
            if ($exception->isWorthRetrying) {
                throw $exception;
            }

            $this->fail($exception);

            return;
        }

        $this->message->forceFill([
            'status' => MessageStatus::Delivered,
            'sent_at' => now(),
            'failure_reason' => null,
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        app(MarkMessageFailed::class)->handle($this->message, $exception?->getMessage() ?? 'The SMS could not be sent.');
    }
}
