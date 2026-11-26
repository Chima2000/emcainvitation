<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('the same guest cannot be added twice on one event', function (array $existing, array $again, string $error) {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create($existing);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.guests.store', $event), [
            ...$again,
            'party_size' => 1,
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors([
            $error => $error === 'phone'
                ? __(':name is already on this event with this phone.', ['name' => 'Asha Mushi'])
                : __('This guest is already on the event.'),
        ]);

    expect($event->guests()->count())->toBe(1);
})->with([
    'phone written another way' => [
        ['name' => 'Asha Mushi', 'phone' => '0712345678', 'email' => 'asha@example.com'],
        ['name' => 'Asha', 'phone' => '+255 712 345 678', 'email' => 'other@example.com'],
        'phone',
    ],
    'email in another case' => [
        ['name' => 'Asha Mushi', 'phone' => '0712345678', 'email' => 'Asha@Example.com'],
        ['name' => 'Neema', 'phone' => '0722000000', 'email' => 'asha@example.com'],
        'email',
    ],
    'name when there is no phone or email' => [
        ['name' => 'Asha Mushi', 'phone' => null, 'email' => null],
        ['name' => 'asha mushi'],
        'name',
    ],
]);

test('two guests may share a name when their phone numbers differ', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'phone' => '0712345678',
    ]);

    $this->actingAs($host)
        ->post(route('events.guests.store', $event), [
            'name' => 'Asha Mushi',
            'phone' => '0733000000',
            'party_size' => 1,
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasNoErrors();

    expect($event->guests()->count())->toBe(2);
});

test('the same phone can be used on a different event', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $otherEvent = Event::factory()->for($host)->create();
    Guest::factory()->for($otherEvent)->create([
        'name' => 'Asha Mushi',
        'phone' => '0712345678',
        'email' => 'asha@example.com',
    ]);

    $this->actingAs($host)
        ->post(route('events.guests.store', $event), [
            'name' => 'Asha Mushi',
            'phone' => '0712345678',
            'email' => 'asha@example.com',
            'party_size' => 1,
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasNoErrors();

    expect($event->guests()->count())->toBe(1);
});

test('editing a guest can keep their own contact details', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $guest = Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'phone' => '0712345678',
        'email' => 'asha@example.com',
    ]);
    $other = Guest::factory()->for($event)->create([
        'name' => 'Neema Lyimo',
        'phone' => '0722000000',
        'email' => 'neema@example.com',
    ]);

    $this->actingAs($host)
        ->put(route('events.guests.update', [$event, $guest]), [
            'name' => 'Asha Mushi',
            'phone' => '0712 345 678',
            'email' => 'asha@example.com',
            'party_size' => 2,
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasNoErrors();

    $this->actingAs($host)
        ->from(route('events.guests.edit', [$event, $other]))
        ->put(route('events.guests.update', [$event, $other]), [
            'name' => 'Neema Lyimo',
            'phone' => '255712345678',
            'email' => 'neema@example.com',
            'party_size' => 1,
        ])
        ->assertRedirect(route('events.guests.edit', [$event, $other]))
        ->assertSessionHasErrors([
            'phone' => __(':name is already on this event with this phone.', ['name' => 'Asha Mushi']),
        ]);

    expect($other->refresh()->phone)->toBe('0722000000');
});

test('an import skips guests already on the event and repeated rows in the file', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'phone' => '0712345678',
        'email' => 'asha@example.com',
    ]);

    $this->actingAs($host)
        ->post(route('events.guests.import', $event), [
            'file' => UploadedFile::fake()->createWithContent(
                'guests.csv',
                "name,phone,email,gender,party_size\nAsha Mushi,255712345678,asha@example.com,female,2\nNeema Lyimo,0722000000,neema@example.com,female,1\nAsha Mushi,+255712345678,different@example.com,female,1\nJuma Said,,juma@example.com,male,1\nJuma Said,,JUMA@example.com,male,1\n",
            ),
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHas('status', trans_choice('Imported :count guest.|Imported :count guests.', 2, ['count' => 2]).' '.__(':count already on the list were left out.', ['count' => 3]));

    expect($event->guests()->pluck('name')->sort()->values()->all())->toBe([
        'Asha Mushi',
        'Juma Said',
        'Neema Lyimo',
    ]);

    $this->post(route('events.guests.import', $event), [
        'file' => UploadedFile::fake()->createWithContent(
            'again.csv',
            "name,phone\nNeema Lyimo,0722000000\n",
        ),
    ])->assertRedirect(route('events.show', $event))
        ->assertSessionHas('status', __('Those guests are already on this event.'));

    expect($event->guests()->count())->toBe(3);
});

test('the guest table stays closed until the host opens it or searches', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create(['name' => 'Asha Mushi']);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSeeInOrder([__('Show guests'), __('Name, phone, or card'), 'id="guest-list"'], false)
        ->assertSee('id="guest-list" data-guest-list hidden', false)
        ->assertSee('aria-expanded="false"', false);

    $this->get(route('events.show', [$event, 'q' => 'Asha']))
        ->assertOk()
        ->assertSee(__('Hide guests'))
        ->assertSee('aria-expanded="true"', false)
        ->assertDontSee('data-guest-list hidden', false);
});
