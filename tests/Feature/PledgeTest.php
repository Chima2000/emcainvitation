<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\Pledge;
use App\Models\User;
use App\PledgeStatus;

test('a saved pledge is announced only in the pledges section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $guest = Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'phone' => '0712000111',
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->followingRedirects()
        ->post(route('events.pledges.store', $event), [
            'guest_id' => $guest->id,
            'donor_name' => 'Family gift',
            'amount' => 50000,
            'status' => PledgeStatus::Pending->value,
        ])
        ->assertOk()
        ->assertSeeInOrder(['name="guest_id"', 'name="donor_name"'], false)
        ->assertSee('data-pledge-phone="0712000111"', false)
        ->assertSee('data-pledge-notice', false)
        ->assertSee(__('Pledge recorded.'), false)
        ->assertDontSee('role="status" data-emca-dismiss', false);
});

test('a host can edit and save a pledge', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $guest = Guest::factory()->for($event)->create();
    $pledge = $event->pledges()->create([
        'donor_name' => 'Family gift',
        'amount' => 50000,
        'status' => PledgeStatus::Pending,
    ]);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('data-emca-confirm="'.e(__('Remove this pledge?')).'"', false)
        ->assertSee(__('Save pledge'), false);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->patch(route('events.pledges.update', [$event, $pledge]), [
            'guest_id' => $guest->id,
            'donor_name' => 'Wedding gift',
            'phone' => '0712000000',
            'amount' => 80000,
            'status' => PledgeStatus::Received->value,
            'note' => 'Paid',
        ])
        ->assertRedirect(route('events.show', $event).'#pledges')
        ->assertSessionHas('pledge_notice', __('Pledge updated.'))
        ->assertSessionMissing('status');

    expect($pledge->refresh()->only(['guest_id', 'donor_name', 'amount', 'note', 'status']))->toBe([
        'guest_id' => $guest->id,
        'donor_name' => 'Wedding gift',
        'amount' => 80000,
        'note' => 'Paid',
        'status' => PledgeStatus::Received,
    ]);
});

test('changing a pledge status stays in the pledges section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $pledge = $event->pledges()->create([
        'donor_name' => 'Family gift',
        'amount' => 50000,
        'status' => PledgeStatus::Pending,
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->patch(route('events.pledges.update', [$event, $pledge]), [
            'status' => PledgeStatus::Received->value,
        ])
        ->assertRedirect(route('events.show', $event).'#pledges')
        ->assertSessionHas('pledge_notice', __('Pledge updated.'))
        ->assertSessionMissing('status');

    expect($pledge->refresh()->status)->toBe(PledgeStatus::Received);
    expect($pledge->donor_name)->toBe('Family gift');
});

test('another host cannot change a pledge', function () {
    $event = Event::factory()->create();
    $pledge = $event->pledges()->create([
        'donor_name' => 'Family gift',
        'amount' => 50000,
        'status' => PledgeStatus::Pending,
    ]);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->patch(route('events.pledges.update', [$event, $pledge]), [
            'donor_name' => 'Changed',
            'amount' => 1,
            'status' => PledgeStatus::Received->value,
        ])
        ->assertNotFound();

    expect($pledge->refresh()->donor_name)->toBe('Family gift');
});

test('removing a pledge keeps the notice in the pledges section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $pledge = $event->pledges()->create([
        'donor_name' => 'Family gift',
        'amount' => 50000,
        'status' => PledgeStatus::Pending,
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->delete(route('events.pledges.destroy', [$event, $pledge]))
        ->assertRedirect(route('events.show', $event).'#pledges')
        ->assertSessionHas('pledge_notice', __('Pledge removed.'))
        ->assertSessionMissing('status');

    expect(Pledge::query()->whereKey($pledge->id)->exists())->toBeFalse();
});
