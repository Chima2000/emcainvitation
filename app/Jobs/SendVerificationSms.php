<?php

namespace App\Jobs;

use App\Exceptions\SmsNotSent;
use App\QueueName;
use App\Support\SmsGateway;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

/**
 * Texts a registration or password reset code. It runs on the urgent queue so a code is never stuck behind
 * bulk guest messages, and the payload is encrypted because it holds the code itself.
 */
#[Queue(QueueName::Urgent)]
#[Tries(3)]
#[Backoff(2, 5)]
#[Timeout(25)]
class SendVerificationSms implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $phone,
        public string $text,
    ) {}

    public function handle(SmsGateway $smsGateway): void
    {
        try {
            $smsGateway->deliver($this->phone, $this->text);
        } catch (SmsNotSent $exception) {
            if ($exception->isWorthRetrying) {
                throw $exception;
            }

            $this->fail($exception);
        }
    }
}
