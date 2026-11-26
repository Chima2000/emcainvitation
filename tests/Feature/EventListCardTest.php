<?php

use App\Models\Event;
use App\Models\InvitationTemplate;
use App\Models\User;

test('event cards use the colours of the chosen invitation card', function () {
    $host = User::factory()->create();
    $garden = InvitationTemplate::factory()->create([
        'paper' => '#1b4d3e',
        'ink' => '#f4efe6',
        'accent' => '#d4a017',
    ]);
    $gold = InvitationTemplate::factory()->create([
        'paper' => '#f6e7c1',
        'ink' => '#3b2a1a',
        'accent' => '#8c1d18',
    ]);

    Event::factory()->for($host)->create([
        'title' => 'Garden Lunch',
        'invitation_template_id' => $garden->id,
    ]);
    Event::factory()->for($host)->create([
        'title' => 'Gold Evening',
        'invitation_template_id' => $gold->id,
        'card_design' => [
            'paper' => '#111111',
            'ink' => '#f7f7f7',
            'accent' => '#ffcc00',
        ],
    ]);
    Event::factory()->for($host)->create([
        'title' => 'Plain Gathering',
    ]);

    $this->actingAs($host)
        ->get(route('events.index'))
        ->assertOk()
        ->assertSeeInOrder(['Garden Lunch', 'Gold Evening', 'Plain Gathering'])
        ->assertSee('Garden Lunch')
        ->assertSee('--event-paper: #1b4d3e', false)
        ->assertSee('--event-ink: #f4efe6', false)
        ->assertSee('--event-accent: #d4a017', false)
        ->assertSee('--event-paper: #111111', false)
        ->assertSee('--event-ink: #f7f7f7', false)
        ->assertSee('--event-accent: #ffcc00', false)
        ->assertSee('Plain Gathering')
        ->assertSee('--event-paper: #faf7f7', false);
});
