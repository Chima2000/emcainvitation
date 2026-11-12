<?php

use App\Exceptions\SmsNotSent;
use App\Jobs\SendGuestSms;
use App\MessagePurpose;
use App\MessageStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\OutboundMessage;
use App\Models\Package;
use App\Models\User;
use App\QueueName;
use App\Support\SmsGateway;
use App\WalletEntryType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

test('sms invitations are queued for the gateway so the host never waits', function () {
    fakeSmsGateway();
    Queue::fake();
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->count(2)->create(['phone' => '0712345678']);

    $this->actingAs($host)
        ->post(route('events.invitations.send', $event), ['channel' => 'sms'])
        ->assertRedirect(route('events.show', $event).'#messages')
        ->assertSessionHas('status');

    Queue::assertPushedOn(QueueName::Messages->value, SendGuestSms::class);
    Queue::assertPushed(SendGuestSms::class, 2);
    Http::assertNothingSent();

    expect(OutboundMessage::query()->pluck('status')->unique()->all())->toBe([MessageStatus::Queued])
        ->and(OutboundMessage::query()->whereNotNull('sent_at')->count())->toBe(0)
        ->and($host->fresh()->wallet_balance)->toBe(10000 - 2180);
});

test('a queued guest sms is delivered through the gateway and marked as sent', function () {
    fakeSmsGateway();
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create(['phone' => '0712345678']);

    $this->actingAs($host)->post(route('events.invitations.send', $event), ['channel' => 'sms']);

    $message = OutboundMessage::query()->sole();

    expect($message->status)->toBe(MessageStatus::Delivered)
        ->and($message->sent_at)->not->toBeNull()
        ->and(lastSentSms()['to'])->toBe('255712345678')
        ->and(lastSentSms()['text'])->toBe($message->body);
});

test('guests without a phone number are skipped and not charged when sms goes through the gateway', function () {
    fakeSmsGateway();
    Queue::fake();
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create(['phone' => '0712345678']);
    $guestWithoutPhone = Guest::factory()->for($event)->create(['phone' => null]);

    $this->actingAs($host)
        ->post(route('events.invitations.send', $event), ['channel' => 'sms'])
        ->assertSessionMissing('wallet_paused');

    expect(OutboundMessage::query()->count())->toBe(1)
        ->and($host->fresh()->wallet_balance)->toBe(10000 - 1090)
        ->and($guestWithoutPhone->fresh()->sent_at)->toBeNull();
});

test('without a gateway sms invitations stay prepared for the host to send by hand', function () {
    Queue::fake();
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create(['phone' => '0712345678']);

    $this->actingAs($host)->post(route('events.invitations.send', $event), ['channel' => 'sms']);

    Queue::assertNothingPushed();
    expect(OutboundMessage::query()->sole()->status)->toBe(MessageStatus::Sent);
});

test('whatsapp messages are never sent through the sms gateway', function () {
    fakeSmsGateway();
    Queue::fake();
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create(['phone' => '0712345678']);

    $this->actingAs($host)->post(route('events.invitations.send', $event), ['channel' => 'whatsapp']);

    Queue::assertNothingPushed();
    expect(OutboundMessage::query()->sole()->status)->toBe(MessageStatus::Sent);
});

test('an sms the gateway rejects is marked failed, refunded, and the guest can be invited again', function () {
    fakeSmsGateway(status: 400);
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    $guest = Guest::factory()->for($event)->create(['phone' => '0712345678']);

    $this->actingAs($host)->post(route('events.invitations.send', $event), ['channel' => 'sms']);

    $message = OutboundMessage::query()->sole();
    $refund = $host->walletTransactions()->latest('id')->first();

    expect($message->status)->toBe(MessageStatus::Failed)
        ->and($message->failure_reason)->toContain('400')
        ->and($message->cost)->toBe(0)
        ->and($host->fresh()->wallet_balance)->toBe(10000)
        ->and($refund->type)->toBe(WalletEntryType::MessageCharge)
        ->and($refund->amount)->toBe(1090)
        ->and($guest->fresh()->sent_at)->toBeNull();

    Queue::fake();
    $this->actingAs($host)->post(route('events.invitations.send', $event), ['channel' => 'sms']);

    Queue::assertPushed(SendGuestSms::class, fn (SendGuestSms $job): bool => $job->message->guest_id === $guest->id);
});

test('a gateway outage keeps the message queued so the worker retries it', function () {
    fakeSmsGateway(status: 503);
    $event = Event::factory()->create();
    $guest = Guest::factory()->for($event)->create(['phone' => '0712345678']);
    $message = OutboundMessage::factory()->for($event)->queuedSms()->create(['guest_id' => $guest->id]);

    expect(fn () => (new SendGuestSms($message))->handle(app(SmsGateway::class)))
        ->toThrow(SmsNotSent::class);

    expect($message->fresh()->status)->toBe(MessageStatus::Queued);
});

test('a failed message covered by the package gives the package credit back', function () {
    $host = User::factory()->create(['wallet_balance' => 300000]);
    $event = Event::factory()->for($host)->create();
    $package = Package::factory()->create(['price' => 120000, 'guest_limit' => 1]);
    $guest = Guest::factory()->for($event)->create(['phone' => '0712345678']);

    $this->actingAs($host)->post(route('events.package.store', $event), ['package_id' => $package->id]);

    $message = OutboundMessage::factory()->for($event)->queuedSms()->create([
        'guest_id' => $guest->id,
        'covered_by_package' => true,
    ]);

    expect($event->fresh()->packageAllowanceLeft(MessagePurpose::Invitation))->toBe(0);

    (new SendGuestSms($message))->failed(new RuntimeException('Gateway down'));
    (new SendGuestSms($message))->failed(new RuntimeException('Gateway down'));

    expect($message->fresh()->status)->toBe(MessageStatus::Failed)
        ->and($event->fresh()->packageAllowanceLeft(MessagePurpose::Invitation))->toBe(1)
        ->and($host->fresh()->wallet_balance)->toBe(300000 - 120000);
});

test('a reminder that failed today can be sent again but a queued one is not doubled', function () {
    fakeSmsGateway();
    Queue::fake();
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    $failedGuest = Guest::factory()->for($event)->create(['phone' => '0712345678']);
    $queuedGuest = Guest::factory()->for($event)->create(['phone' => '0713345678']);

    foreach ([[$failedGuest, MessageStatus::Failed], [$queuedGuest, MessageStatus::Queued]] as [$guest, $status]) {
        OutboundMessage::factory()->for($event)->queuedSms()->create([
            'guest_id' => $guest->id,
            'purpose' => MessagePurpose::Reminder,
            'status' => $status,
        ]);
    }

    $this->actingAs($host)->post(route('events.reminders.send', $event), ['channel' => 'sms']);

    Queue::assertPushed(SendGuestSms::class, 1);
    expect($failedGuest->messages()->where('status', MessageStatus::Queued)->count())->toBe(1);
});

test('the admin sees when messages are stuck because no queue worker is running', function () {
    config(['queue.default' => 'database']);
    DB::table('jobs')->insert([
        'queue' => QueueName::Urgent->value,
        'payload' => '{}',
        'attempts' => 0,
        'available_at' => now()->subMinutes(5)->getTimestamp(),
        'created_at' => now()->subMinutes(5)->getTimestamp(),
    ]);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('id="message-queue"', false)
        ->assertSee(__('Needs attention'))
        ->assertSee('queue:work --queue=sms-urgent,messages,default');
});

test('the admin can clear the failed jobs list', function () {
    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => QueueName::Messages->value,
        'payload' => '{}',
        'exception' => 'Gateway down',
        'failed_at' => now(),
    ]);

    $hostResponse = $this->actingAs(User::factory()->create())->delete(route('admin.settings.queue-flush'));

    expect($hostResponse->status())->toBeIn([403, 404])
        ->and(DB::table('failed_jobs')->count())->toBe(1);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->delete(route('admin.settings.queue-flush'))
        ->assertRedirect(route('admin.settings.edit'));

    expect(DB::table('failed_jobs')->count())->toBe(0);
});
