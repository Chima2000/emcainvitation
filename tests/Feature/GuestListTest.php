<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;

test('the guest table keeps guests after the fifth behind next', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    foreach (range(1, 6) as $minutesAgo) {
        Guest::factory()->for($event)->create([
            'name' => 'Guest '.$minutesAgo,
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    $page = $this->actingAs($host)->get(route('events.show', $event));

    $page->assertOk()
        ->assertSee(__('Next'), false)
        ->assertSee('data-guest-row-next', false)
        ->assertSee('Guest 1')
        ->assertSee('Guest 6');

    expect($page->getContent())->toMatch('/data-guest-row\s+hidden[\s\S]*?Guest 6/');
});

test('the guest table shows every guest when there are five or fewer', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    foreach (range(1, 5) as $minutesAgo) {
        Guest::factory()->for($event)->create([
            'name' => 'Guest '.$minutesAgo,
            'created_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertDontSee('data-guest-row-next', false)
        ->assertDontSee('data-guest-row hidden', false);
});
