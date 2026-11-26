<?php

use App\Models\Event;
use App\Models\User;

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('an invalid new event stays in the popup', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('events.store'), [
            'title' => '',
        ])
        ->assertRedirect(route('events.index', ['new' => 1]))
        ->assertSessionHasErrors('title', errorBag: 'createEvent');

    $this->actingAs($user)
        ->get(route('events.index', ['new' => 1]))
        ->assertOk()
        ->assertSee('data-emca-open="1"', false)
        ->assertSeeInOrder([
            '<optgroup label="Harusi na hatua muhimu">',
            '<option value="wedding"',
            '<option value="muslim_wedding"',
            '</optgroup>',
            '<optgroup label="Imani na kumbukumbu">',
            '<option value="ramadan"',
        ], false);
});

test('the new event form stays blank while another event is open', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create([
        'title' => 'Harusi ya Neema',
        'venue' => 'Serena Hotel Arusha',
        'partner_name' => 'Juma Rashid',
        'headline' => 'Karibu kwenye harusi',
        'message' => 'Tafadhali fika mapema sana',
        'dress_code' => 'Formal attire only',
    ]);

    $page = $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Harusi ya Neema');

    preg_match('/<form method="POST" action="[^"]*" autocomplete="off" data-event-form data-event-preview-form[\s\S]*?<\/form>/', $page->getContent(), $matches);

    expect($matches[0] ?? '')
        ->toContain('data-card-field="title" hidden')
        ->toContain('data-card-field="when" hidden')
        ->toContain('data-card-field="venue" hidden')
        ->not->toContain('Harusi ya Neema')
        ->not->toContain('Serena Hotel Arusha')
        ->not->toContain('Juma Rashid')
        ->not->toContain('Karibu kwenye harusi')
        ->not->toContain('Tafadhali fika mapema sana')
        ->not->toContain('Formal attire only');

    $this->actingAs($host)
        ->get(route('events.edit', $event))
        ->assertOk()
        ->assertSee('value="Harusi ya Neema"', false)
        ->assertSee('data-card-field="title">Harusi ya Neema', false)
        ->assertSee('data-card-field="venue">Serena Hotel Arusha', false)
        ->assertSee('Karibu kwenye harusi');
});

test('a failed new event keeps the details the host just typed', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->from(route('events.index'))
        ->followingRedirects()
        ->post(route('events.store'), [
            'title' => 'Harusi mpya',
            'type' => 'wedding',
            'venue' => '',
        ])
        ->assertOk()
        ->assertSee('value="Harusi mpya"', false)
        ->assertSee('data-emca-open="1"', false);
});
