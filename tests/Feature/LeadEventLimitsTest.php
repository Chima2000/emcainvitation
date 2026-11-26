<?php

use App\EventStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\InvitationTemplate;
use App\Models\User;
use App\UserRole;

test('a lead creates a draft with a free template and cannot publish it', function () {
    $free = InvitationTemplate::factory()->create(['name' => 'Plain Rose', 'host_only' => false]);
    $paid = InvitationTemplate::factory()->create(['name' => 'Harusi Gold', 'host_only' => true]);
    $lead = User::factory()->create(['role' => UserRole::Lead]);

    $this->actingAs($lead)
        ->get(route('events.index'))
        ->assertOk()
        ->assertSee(__('For hosts'))
        ->assertSee(__('Publishing and the public list open after you become a host.'))
        ->assertDontSee('id="create-event-is_public"', false);

    $details = [
        'title' => 'Mahafali ya Neema',
        'type' => 'wedding',
        'host_name' => 'Neema',
        'venue' => 'Arusha',
        'event_date' => now()->addMonth()->format('Y-m-d H:i'),
    ];

    $this->actingAs($lead)
        ->post(route('events.store'), [
            ...$details,
            'invitation_template_id' => $paid->id,
            'is_public' => '1',
            'status' => EventStatus::Published->value,
        ])
        ->assertSessionHasErrors('invitation_template_id', errorBag: 'createEvent');

    expect($lead->events()->count())->toBe(0);

    $this->actingAs($lead)
        ->post(route('events.store'), [
            ...$details,
            'invitation_template_id' => $free->id,
            'is_public' => '1',
            'status' => EventStatus::Published->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $event = $lead->events()->sole();

    expect($event->invitation_template_id)->toBe($free->id)
        ->and($event->is_public)->toBeFalse()
        ->and($event->status)->toBe(EventStatus::Draft);
});

test('a lead can save card wording but not a custom colour or a paid template', function () {
    $free = InvitationTemplate::factory()->create(['name' => 'Plain Rose', 'host_only' => false, 'accent' => '#940000']);
    $paid = InvitationTemplate::factory()->create(['name' => 'Harusi Gold', 'host_only' => true]);
    $lead = User::factory()->create(['role' => UserRole::Lead]);
    $event = Event::factory()->for($lead)->create(['invitation_template_id' => $free->id]);

    $this->actingAs($lead)
        ->get(route('events.design.edit', $event))
        ->assertOk()
        ->assertSee(__('Custom colours, fonts and your own picture open after you become a host.'))
        ->assertDontSee('name="paper"', false)
        ->assertSee('disabled', false);

    $this->actingAs($lead)
        ->put(route('events.design.update', $event), [
            'invitation_template_id' => $paid->id,
            'headline' => 'Karibu',
            'paper' => '#000000',
            'ink' => '#111111',
            'accent' => '#222222',
            'font' => 'playfair',
            'layout' => 'classic',
        ])
        ->assertSessionHasErrors('invitation_template_id');

    expect($event->refresh()->invitation_template_id)->toBe($free->id)
        ->and($event->card_design)->toBeNull();

    $this->actingAs($lead)
        ->put(route('events.design.update', $event), [
            'invitation_template_id' => $free->id,
            'headline' => 'Karibu',
            'paper' => '#000000',
        ])
        ->assertSessionHasNoErrors();

    expect($event->refresh()->headline)->toBe('Karibu')
        ->and($event->card_design)->toBeNull()
        ->and($event->invitation_template_id)->toBe($free->id);
});

test('a lead cannot add an eleventh guest', function () {
    $lead = User::factory()->create(['role' => UserRole::Lead]);
    $event = Event::factory()->for($lead)->create();
    Guest::factory()->for($event)->count(10)->create();

    $this->actingAs($lead)
        ->from(route('events.show', $event))
        ->post(route('events.guests.store', $event), [
            'name' => 'Extra Guest',
            'party_size' => 1,
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors('name');

    expect($event->guests()->count())->toBe(10);

    $this->actingAs($lead)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertDontSee(__('Add guest'), false);
});
