<?php

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
        ->assertSee('data-emca-open="1"', false);
});
