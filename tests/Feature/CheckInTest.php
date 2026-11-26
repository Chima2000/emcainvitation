<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;

test('the door card can scan an invitation code', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('data-scanner-start', false)
        ->assertSee('data-check-in-code', false)
        ->assertSee('data-door-refresh', false)
        ->assertSee('data-door-checking', false)
        ->assertSee(__('Checking the card…'), false)
        ->assertSee('playsinline muted hidden', false)
        ->assertSee('data-scanner-stage', false)
        ->assertSee('data-scanner-line', false)
        ->assertSee('data-scanner-verdict', false)
        ->assertSee('emca-scanner-line', false)
        ->assertSee(__('Scan'))
        ->assertSee(__('Refresh'))
        ->assertSee(__('Show arrivals'))
        ->assertSee('id="door-arrivals" hidden', false);
});

test('the door arrival list keeps guests after the fifth behind next', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    foreach (range(1, 6) as $minutesAgo) {
        Guest::factory()->for($event)->create([
            'name' => 'Arrival '.$minutesAgo,
            'checked_in_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    $page = $this->actingAs($host)->get(route('events.show', $event));

    $page->assertOk()
        ->assertSee(__('Next'), false)
        ->assertSee('data-door-arrival-next', false)
        ->assertSee('data-door-arrival-prev', false)
        ->assertSee('Arrival 1')
        ->assertSee('Arrival 6');

    expect($page->getContent())->toMatch('/data-door-arrival\s+hidden\s*>\s*Arrival 6/');
});

test('scanning an invitation address checks that guest in once', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $guest = Guest::factory()->for($event)->create(['name' => 'Asha Mushi']);

    $this->actingAs($host)
        ->post(route('events.check-ins.store', $event), [
            'code' => route('invitations.show', $guest->token),
        ])
        ->assertRedirect(route('events.show', $event).'#check-in')
        ->assertSessionHas('door_message')
        ->assertSessionHas('check_in', 'verified')
        ->assertSessionMissing('status');

    $checkedInAt = $guest->refresh()->checked_in_at;

    expect($checkedInAt)->not->toBeNull();

    $this->travel(5)->minutes();

    $this->post(route('events.check-ins.store', $event), [
        'code' => $guest->serial,
    ])
        ->assertRedirect(route('events.show', $event).'#check-in')
        ->assertSessionHas('door_message')
        ->assertSessionHas('check_in', 'denied')
        ->assertSessionMissing('error');

    expect($guest->refresh()->checked_in_at->equalTo($checkedInAt))->toBeTrue();
});

test('the typed card number admits only that guest', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $asha = Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'serial' => 'EM-100111',
    ]);
    $juma = Guest::factory()->for($event)->create([
        'name' => 'Juma Said',
        'serial' => 'EM-100222',
    ]);

    $this->actingAs($host)
        ->post(route('events.check-ins.store', $event), [
            'code' => ' em-100111 ',
        ])
        ->assertRedirect(route('events.show', $event).'#check-in')
        ->assertSessionHas('door_message')
        ->assertSessionMissing('status');

    expect($asha->refresh()->checked_in_at)->not->toBeNull()
        ->and($juma->refresh()->checked_in_at)->toBeNull();
});

test('a scanned invitation code admits only that guest', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $asha = Guest::factory()->for($event)->create(['name' => 'Asha Mushi']);
    $juma = Guest::factory()->for($event)->create(['name' => 'Juma Said']);

    $this->actingAs($host)
        ->post(route('events.check-ins.store', $event), [
            'code' => route('invitations.show', $asha->token),
        ])
        ->assertRedirect(route('events.show', $event).'#check-in')
        ->assertSessionHas('door_message')
        ->assertSessionMissing('status');

    expect($asha->refresh()->checked_in_at)->not->toBeNull()
        ->and($juma->refresh()->checked_in_at)->toBeNull();
});

test('a different card, a partial number, or a name does not admit a guest', function (string $code) {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $asha = Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'serial' => 'EM-100111',
        'phone' => '0712345678',
    ]);
    $otherEvent = Event::factory()->for($host)->create();
    $visitor = Guest::factory()->for($otherEvent)->create([
        'serial' => 'EM-100333',
    ]);

    $this->actingAs($host)
        ->post(route('events.check-ins.store', $event), [
            'code' => $code,
        ])
        ->assertRedirect(route('events.show', $event).'#check-in')
        ->assertSessionHas('door_message')
        ->assertSessionHas('check_in', 'denied')
        ->assertSessionMissing('error');

    expect($asha->refresh()->checked_in_at)->toBeNull()
        ->and($visitor->refresh()->checked_in_at)->toBeNull();
})->with([
    'another card number' => 'EM-100222',
    'a card from another event' => 'EM-100333',
    'part of the card number' => 'EM-10011',
    'the guest name' => 'Asha Mushi',
    'the guest phone' => '0712345678',
    'the invitation link buried in other text' => 'Card EM-100111 https://example.test/i/not-a-real-token',
]);

test('opening the same door link twice does not check the guest in twice', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $guest = Guest::factory()->for($event)->create(['name' => 'Asha Mushi']);

    $this->actingAs($host)
        ->get(route('check-ins.scan', $guest->token))
        ->assertOk()
        ->assertSee(__('Checked in'))
        ->assertSee('data-door-result="verified"', false)
        ->assertSee(__(':name is checked in. Card :serial cannot be used again.', [
            'name' => $guest->name,
            'serial' => $guest->serial,
        ]), false);

    $checkedInAt = $guest->refresh()->checked_in_at;

    $this->travel(5)->minutes();

    $this->get(route('check-ins.scan', $guest->token))
        ->assertOk()
        ->assertSee(__('Already used'))
        ->assertSee('data-door-result="denied"', false);

    expect($guest->refresh()->checked_in_at->equalTo($checkedInAt))->toBeTrue()
        ->and($event->guests()->whereNotNull('checked_in_at')->count())->toBe(1);
});

test('the door shows a verification or denial alert', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $guest = Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'serial' => 'EM-100111',
    ]);
    $verified = __(':name is checked in. Card :serial cannot be used again.', [
        'name' => 'Asha Mushi',
        'serial' => 'EM-100111',
    ]);

    $this->actingAs($host)
        ->followingRedirects()
        ->post(route('events.check-ins.store', $event), [
            'code' => 'EM-100111',
        ])
        ->assertOk()
        ->assertSee('data-door-result="verified"', false)
        ->assertSee('alert alert-success', false)
        ->assertDontSee('data-emca-dismiss', false)
        ->assertSee($verified, false);

    $this->followingRedirects()
        ->post(route('events.check-ins.store', $event), [
            'code' => 'EM-100111',
        ])
        ->assertOk()
        ->assertSee('data-door-result="denied"', false)
        ->assertSee('alert alert-danger', false)
        ->assertDontSee('data-emca-dismiss', false)
        ->assertSee(__(':name already used this card at :time.', [
            'name' => 'Asha Mushi',
            'time' => $guest->refresh()->checked_in_at->timezone(config('app.timezone'))->translatedFormat('g:i A'),
        ]), false);

    $this->followingRedirects()
        ->post(route('events.check-ins.store', $event), [
            'code' => 'NO-SUCH-CARD',
        ])
        ->assertOk()
        ->assertSee('data-door-result="denied"', false)
        ->assertSee(__('No invitation matches that code.'), false);
});
