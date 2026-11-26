<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\UserRole;

test('a committee member can check guests in from a door link', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create(['title' => 'Harusi ya Asha']);
    $staff = Guest::factory()->for($event)->create(['name' => 'Baraka Mollel', 'phone' => '0711111111']);
    $guest = Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'phone' => '0712345678',
        'party_size' => 3,
        'table_name' => 'High table',
        'serial' => 'EM-366693',
    ]);
    $committee = $event->committees()->create([
        'name' => 'Door',
        'leader_guest_id' => $staff->id,
        'is_door' => true,
    ]);
    $committee->guests()->sync([$staff->id]);

    $this->actingAs($host)
        ->post(route('events.committees.door', [$event, $committee]), ['guest_id' => $staff->id])
        ->assertRedirect();

    $token = $staff->refresh()->door_token;

    expect($token)->not->toBeNull();

    $this->get(route('doors.show', $token))
        ->assertOk()
        ->assertSee('Baraka Mollel')
        ->assertSee('data-door-card', false)
        ->assertSee('data-door-refresh', false)
        ->assertSee('data-scanner-verdict', false)
        ->assertSee('data-scanner-line', false)
        ->assertSee(__('Checking the card…'), false)
        ->assertSee(__('Refresh'), false)
        ->assertSee(__('Show arrivals'), false)
        ->assertSee('id="door-arrivals" hidden', false)
        ->assertDontSee('0711111111')
        ->assertDontSee(route('wallet.index'), false);

    $this->post(route('doors.store', $token), ['code' => 'EM-366693'])
        ->assertRedirect(route('doors.show', $token))
        ->assertSessionHas('check_in', 'verified');

    $this->get(route('doors.show', $token))
        ->assertOk()
        ->assertSee('data-door-result="verified"', false)
        ->assertSee('Asha Mushi')
        ->assertSee(__('Party size :count', ['count' => 3]))
        ->assertSee('High table')
        ->assertDontSee('0712345678');

    expect($guest->refresh()->checked_in_at)->not->toBeNull();
});

test('a door link stops when the person leaves every committee', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $staff = Guest::factory()->for($event)->create(['door_token' => 'door-token-asha']);
    $committee = $event->committees()->create([
        'name' => 'Door',
        'leader_guest_id' => $staff->id,
        'is_door' => true,
        'door_guest_id' => $staff->id,
    ]);
    $committee->guests()->sync([$staff->id]);

    $this->actingAs($host)
        ->delete(route('events.committees.destroy', [$event, $committee]))
        ->assertRedirect();

    expect($staff->refresh()->door_token)->toBeNull();

    $this->get(route('doors.show', 'door-token-asha'))->assertNotFound();
});

test('a trial cannot open a door link', function () {
    $lead = User::factory()->create(['role' => UserRole::Lead]);
    $event = Event::factory()->for($lead)->create();
    $staff = Guest::factory()->for($event)->create();
    $committee = $event->committees()->create([
        'name' => 'Door',
        'leader_guest_id' => $staff->id,
        'is_door' => true,
    ]);
    $committee->guests()->sync([$staff->id]);

    $this->actingAs($lead)
        ->post(route('events.committees.door', [$event, $committee]), ['guest_id' => $staff->id])
        ->assertRedirect(route('become-host.create'));

    expect($staff->refresh()->door_token)->toBeNull();
});

test('members of the one door committee can each hold a door link', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $holder = Guest::factory()->for($event)->create(['name' => 'Baraka Mollel']);
    $other = Guest::factory()->for($event)->create(['name' => 'Asha Mushi']);
    $outsider = Guest::factory()->for($event)->create(['name' => 'Neema Lyimo']);

    $this->actingAs($host)
        ->post(route('events.committees.store', $event), [
            'name' => 'Door',
            'leader_guest_id' => $holder->id,
            'participant_ids' => [$holder->id, $other->id],
            'is_door' => '1',
            'door_guest_ids' => [$holder->id],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $committee = $event->committees()->where('name', 'Door')->first();
    $token = $holder->refresh()->door_token;

    expect($committee->is_door)->toBeTrue()
        ->and($token)->not->toBeNull()
        ->and($other->refresh()->door_token)->toBeNull();

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee(__('Door committee'))
        ->assertSee(route('doors.show', $token), false)
        ->assertDontSee(__('This committee works the door'));

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.committees.store', $event), [
            'name' => 'Food',
            'leader_guest_id' => $outsider->id,
            'participant_ids' => [$outsider->id],
            'is_door' => '1',
            'door_guest_ids' => [$outsider->id],
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors([
            'is_door' => __('This event already has a door committee.'),
        ]);

    expect($event->committees()->where('is_door', true)->count())->toBe(1);

    $food = $event->committees()->create([
        'name' => 'Food',
        'leader_guest_id' => $outsider->id,
    ]);
    $food->guests()->sync([$outsider->id]);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee(route('events.committees.door', [$event, $committee]), false)
        ->assertDontSee(route('events.committees.door', [$event, $food]), false);

    $this->actingAs($host)
        ->post(route('events.committees.door', [$event, $food]), ['guest_id' => $outsider->id])
        ->assertNotFound();

    $page = $this->actingAs($host)
        ->from(route('events.show', $event))
        ->followingRedirects()
        ->post(route('events.committees.door', [$event, $committee]), ['guest_id' => $other->id]);

    $page->assertOk()
        ->assertSee('data-committee-notice', false)
        ->assertSee('data-emca-persist', false)
        ->assertSee(__('Door link for :name is ready.', ['name' => 'Asha Mushi']), false)
        ->assertSee(__('Remove :name from the door link', ['name' => 'Asha Mushi']), false)
        ->assertSee(__('Remove :name from the door link', ['name' => 'Baraka Mollel']), false)
        ->assertSee(route('doors.show', $holder->door_token), false)
        ->assertSee(__('Remove this committee from the door link'))
        ->assertDontSee('id="committee-list" hidden', false)
        ->assertDontSee('role="status" data-emca-dismiss', false);

    expect($page->getContent())->not->toMatch('/id="add-committee-'.$committee->id.'" data-committee-add="'.$committee->id.'"\s+hidden/');

    $holderToken = $holder->refresh()->door_token;
    $otherToken = $other->refresh()->door_token;

    expect($holderToken)->not->toBeNull()
        ->and($otherToken)->not->toBeNull()
        ->and($holderToken)->not->toBe($otherToken);

    $this->get(route('doors.show', $holderToken))->assertOk();
    $this->get(route('doors.show', $otherToken))->assertOk();

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->followingRedirects()
        ->delete(route('events.committees.door.close', [$event, $committee]), ['guest_id' => $other->id])
        ->assertOk()
        ->assertSee('data-committee-notice', false)
        ->assertSee(__(':name was removed from the door link.', ['name' => 'Asha Mushi']), false);

    expect($committee->refresh()->is_door)->toBeTrue()
        ->and($holder->refresh()->door_token)->toBe($holderToken)
        ->and($other->refresh()->door_token)->toBeNull();

    $this->get(route('doors.show', $holderToken))->assertOk();
    $this->get(route('doors.show', $otherToken))->assertNotFound();

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->followingRedirects()
        ->delete(route('events.committees.door.release', [$event, $committee]))
        ->assertOk()
        ->assertSee(__('This committee was removed from the door link.'), false);

    expect($committee->refresh()->is_door)->toBeFalse()
        ->and($holder->refresh()->door_token)->toBeNull()
        ->and($other->refresh()->door_token)->toBeNull();

    $this->get(route('doors.show', $token))->assertNotFound();
});

test('each door link must belong to a selected member', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $leader = Guest::factory()->for($event)->create();
    $other = Guest::factory()->for($event)->create();

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.committees.store', $event), [
            'name' => 'Door',
            'leader_guest_id' => $leader->id,
            'participant_ids' => [$leader->id],
            'is_door' => '1',
            'door_guest_ids' => [$other->id],
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors([
            'door_guest_ids' => __('Each door link must belong to a selected member of this committee.'),
        ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.committees.store', $event), [
            'name' => 'Door',
            'leader_guest_id' => $leader->id,
            'participant_ids' => [$leader->id],
            'is_door' => '1',
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors([
            'door_guest_ids' => __('Each door link must belong to a selected member of this committee.'),
        ]);

    expect($event->committees()->count())->toBe(0);
});

test('the committee door keeps guests after the fifth behind next', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $staff = Guest::factory()->for($event)->create(['door_token' => 'door-page-token']);
    $committee = $event->committees()->create([
        'name' => 'Door',
        'leader_guest_id' => $staff->id,
        'is_door' => true,
        'door_guest_id' => $staff->id,
    ]);
    $committee->guests()->sync([$staff->id]);

    foreach (range(1, 6) as $minutesAgo) {
        Guest::factory()->for($event)->create([
            'name' => 'Arrival '.$minutesAgo,
            'checked_in_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    $page = $this->get(route('doors.show', 'door-page-token'));

    $page->assertOk()
        ->assertSee(__('Next'), false)
        ->assertSee('data-door-arrival-next', false);

    expect($page->getContent())->toMatch('/data-door-arrival\s+hidden\s*>\s*Arrival 6/');
});
