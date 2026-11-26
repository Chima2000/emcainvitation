<?php

use App\Actions\RememberGuestsAsContacts;
use App\EventStatus;
use App\MessagePurpose;
use App\Models\Event;
use App\Models\Guest;
use App\Models\OutboundMessage;
use App\Models\User;
use App\PledgeStatus;

test('adding a guest keeps them in the contact book for the next event', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)->post(route('events.guests.store', $event), [
        'name' => 'Neema Joseph',
        'phone' => '0712345678',
        'party_size' => 1,
    ])->assertRedirect();

    $contact = $host->contacts()->where('phone', '0712345678')->first();

    expect($contact?->name)->toBe('Neema Joseph');

    $second = Event::factory()->for($host)->create();

    $this->actingAs($host)->post(route('events.guests.store', $second), [
        'contact_id' => $contact->id,
        'party_size' => 1,
    ])->assertRedirect();

    expect($second->guests()->sole()->name)->toBe('Neema Joseph')
        ->and($second->guests()->sole()->phone)->toBe('0712345678');
});

test('a guest group receives the invitation while the other waiting guests stay unprepared', function () {
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    $chosen = Guest::factory()->for($event)->create(['name' => 'Chosen Guest']);
    Guest::factory()->for($event)->create(['name' => 'Waiting Guest']);

    $this->actingAs($host)->post(route('events.groups.store', $event), [
        'name' => 'Family table',
        'guest_ids' => [$chosen->id],
    ])->assertRedirect(route('events.show', $event).'#guests');

    $group = $event->guestGroups()->sole();

    $this->actingAs($host)->post(route('events.invitations.send', $event), [
        'channel' => 'whatsapp',
        'audience' => 'group',
        'group_id' => $group->id,
    ])->assertRedirect();

    expect(OutboundMessage::query()->count())->toBe(1)
        ->and($chosen->fresh()->sent_at)->not->toBeNull()
        ->and($event->guests()->whereNull('sent_at')->count())->toBe(1);
});

test('preparing without an audience still reaches every waiting guest', function () {
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->count(2)->create();

    $this->actingAs($host)->post(route('events.invitations.send', $event), [
        'channel' => 'whatsapp',
    ]);

    expect(OutboundMessage::query()->count())->toBe(2);
});

test('a reviewed batch does not start when the wallet cannot finish it', function () {
    $host = User::factory()->create(['wallet_balance' => 1000]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->count(2)->create();

    $this->actingAs($host)
        ->get(route('events.messages.preview', [
            'event' => $event,
            'purpose' => 'invitation',
            'channel' => 'whatsapp',
            'audience' => 'waiting',
        ]))
        ->assertOk()
        ->assertSee(__('This batch needs TZS :amount more in the wallet before it can start.', [
            'amount' => number_format(1600),
        ]), false)
        ->assertDontSee(__('Prepare these messages'), false);

    $this->actingAs($host)
        ->post(route('events.invitations.send', $event), [
            'channel' => 'whatsapp',
            'audience' => 'waiting',
            'reviewed' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(OutboundMessage::query()->count())->toBe(0)
        ->and($host->fresh()->wallet_balance)->toBe(1000);
});

test('a pledge note tells the guest their open balance', function () {
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create(['title' => 'Family Gathering']);
    $guest = Guest::factory()->for($event)->create(['name' => 'Asha Mushi']);
    $event->pledges()->create([
        'guest_id' => $guest->id,
        'donor_name' => 'Family gift',
        'amount' => 50000,
        'status' => PledgeStatus::Pending,
    ]);

    $this->actingAs($host)->post(route('events.pledges.notify', $event), [
        'channel' => 'whatsapp',
        'reviewed' => 1,
    ])->assertRedirect();

    expect(OutboundMessage::query()->sole())
        ->purpose->toBe(MessagePurpose::Pledge)
        ->body->toContain('50,000');
});

test('wedding lines appear on the invitation card only for a wedding', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create([
        'type' => 'birthday',
        'title' => 'Garden Lunch',
        'host_name' => 'Augustino',
        'venue' => 'Johari Rotana',
        'event_date' => now()->addMonth()->toDateString(),
    ]);

    $this->actingAs($host)->put(route('events.update', $event), [
        'title' => 'Garden Lunch',
        'type' => 'wedding',
        'status' => EventStatus::Draft->value,
        'host_name' => 'Augustino',
        'host_name_is_organization' => 0,
        'venue' => 'Johari Rotana',
        'event_date' => $event->event_date->toDateString(),
        'bride_name' => 'Lina',
        'groom_name' => 'Jovin',
        'bride_family' => 'Otto',
    ])->assertRedirect();

    expect($event->fresh()->bride_name)->toBe('Lina')
        ->and($event->fresh()->type?->slug)->toBe('wedding');

    $guest = Guest::factory()->for($event)->create();

    $this->get(route('invitations.show', $guest->token))
        ->assertOk()
        ->assertSee('Lina & Jovin')
        ->assertSee(__('Bride’s family: :name', ['name' => 'Otto']));

    $other = Event::factory()->for($host)->create([
        'type' => 'birthday',
        'bride_name' => 'Lina',
    ]);
    $otherGuest = Guest::factory()->for($other)->create();

    $this->get(route('invitations.show', $otherGuest->token))
        ->assertOk()
        ->assertDontSee('Lina');
});

test('the contact book shows six people then a next page', function () {
    $host = User::factory()->create();

    foreach (range(1, 7) as $number) {
        $host->contacts()->create([
            'name' => 'Person '.$number,
            'phone' => '071100000'.$number,
        ]);
    }

    $this->actingAs($host)
        ->get(route('contacts.index'))
        ->assertOk()
        ->assertSee('Person 1')
        ->assertSee('Person 6')
        ->assertDontSee('Person 7')
        ->assertSee(__('Next'))
        ->assertDontSee(__('Previous'));

    $this->actingAs($host)
        ->get(route('contacts.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Person 7')
        ->assertDontSee('Person 1')
        ->assertSee(__('Previous'))
        ->assertDontSee(__('Next'));
});

test('guests already on events are copied into that host contact book', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'phone' => '0712000111',
        'email' => 'asha@example.com',
    ]);
    Guest::factory()->for(Event::factory()->for($host))->create([
        'name' => 'Asha Mushi',
        'phone' => '0712000111',
        'email' => 'asha.new@example.com',
    ]);
    Guest::factory()->for($event)->create([
        'name' => 'No Phone',
        'phone' => null,
        'email' => null,
    ]);

    $other = User::factory()->create();
    Guest::factory()->for(Event::factory()->for($other))->create([
        'name' => 'Other Host Guest',
        'phone' => '0799000111',
    ]);

    app(RememberGuestsAsContacts::class)->handle();
    app(RememberGuestsAsContacts::class)->handle();

    expect($host->contacts()->orderBy('name')->pluck('name')->all())->toBe(['Asha Mushi', 'No Phone'])
        ->and($host->contacts()->where('phone', '0712000111')->sole()->email)->toBe('asha.new@example.com')
        ->and($other->contacts()->sole()->name)->toBe('Other Host Guest');
});
