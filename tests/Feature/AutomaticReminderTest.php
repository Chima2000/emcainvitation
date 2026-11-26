<?php

use App\EventStatus;
use App\MessagePurpose;
use App\Models\Event;
use App\Models\Guest;
use App\Models\OutboundMessage;
use App\Models\User;
use App\RsvpStatus;
use App\UserRole;

test('one reminder is prepared for guests who have not replied', function () {
    config([
        'services.sms.base_url' => '',
        'services.sms.username' => '',
        'services.sms.password' => '',
    ]);

    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create([
        'status' => EventStatus::Published,
        'event_date' => now()->addDays(2),
        'auto_reminder' => true,
        'reminder_days' => 3,
    ]);
    $waiting = Guest::factory()->for($event)->create(['phone' => '0712345678']);
    $coming = Guest::factory()->for($event)->attending()->create(['phone' => '0722000000']);

    $this->artisan('reminders:send')->assertSuccessful();

    expect($waiting->messages()->where('purpose', MessagePurpose::Reminder)->count())->toBe(1)
        ->and($coming->messages()->where('purpose', MessagePurpose::Reminder)->count())->toBe(0)
        ->and($event->refresh()->auto_reminder_sent_at)->not->toBeNull();

    $this->artisan('reminders:send')->assertSuccessful();

    expect(OutboundMessage::query()->where('purpose', MessagePurpose::Reminder)->count())->toBe(1);
});

test('a stopped reminder is not prepared', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create([
        'status' => EventStatus::Published,
        'event_date' => now()->addDay(),
        'auto_reminder' => false,
        'reminder_days' => 3,
    ]);
    Guest::factory()->for($event)->create(['rsvp_status' => RsvpStatus::Pending]);

    $this->artisan('reminders:send')->assertSuccessful();

    expect(OutboundMessage::query()->count())->toBe(0);
});

test('a host can turn the automatic reminder on and off', function () {
    $host = User::factory()->create();
    $lead = User::factory()->create(['role' => UserRole::Lead]);
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->put(route('events.reminder.update', $event), [
            'auto_reminder' => '1',
            'reminder_days' => 4,
        ])
        ->assertRedirect(route('events.show', $event).'#messages');

    expect($event->refresh()->auto_reminder)->toBeTrue()
        ->and($event->reminder_days)->toBe(4);

    $this->actingAs($host)
        ->put(route('events.reminder.update', $event), [
            'reminder_days' => 4,
        ])
        ->assertRedirect(route('events.show', $event).'#messages');

    expect($event->refresh()->auto_reminder)->toBeFalse();

    $leadEvent = Event::factory()->for($lead)->create();

    $this->actingAs($lead)
        ->put(route('events.reminder.update', $leadEvent), [
            'auto_reminder' => '1',
            'reminder_days' => 3,
        ])
        ->assertRedirect(route('become-host.create'));
});
