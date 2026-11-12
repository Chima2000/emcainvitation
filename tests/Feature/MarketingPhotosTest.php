<?php

use App\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Guest;
use Illuminate\Testing\TestResponse;

/**
 * @return list<string>
 */
function missingLocalPhotos(TestResponse $response): array
{
    preg_match_all('#https?://[^/"\'\s]+/(images/[\w/.-]+\.jpg)#', $response->getContent(), $matches);

    expect($matches[1])->not->toBeEmpty();

    return array_values(array_filter(
        array_unique($matches[1]),
        fn (string $path): bool => ! is_file(public_path($path)),
    ));
}

test('every photo on a public page exists', function (string $routeName) {
    $response = $this->get(route($routeName))->assertOk();

    expect(missingLocalPhotos($response))->toBe([]);
})->with([
    'home' => 'home',
    'features' => 'features',
    'pricing' => 'pricing',
    'privacy' => 'privacy',
    'terms' => 'terms',
    'templates' => 'templates.index',
    'upcoming events' => 'events.upcoming',
]);

test('each event type shows an existing photo on its public card and invitation page', function (string $slug) {
    $event = Event::factory()->create([
        'type' => $slug,
        'status' => EventStatus::Published,
        'is_public' => true,
    ]);
    $guest = Guest::factory()->for($event)->create();

    $upcoming = $this->get(route('events.upcoming'))
        ->assertOk()
        ->assertSee('images/occasions/'.EventCategory::findBySlug($slug)->photo, false);

    $invitation = $this->get(route('invitations.show', $guest->token))
        ->assertOk()
        ->assertSee('images/page-titles/occasions/'.EventCategory::findBySlug($slug)->photo, false);

    expect(missingLocalPhotos($upcoming))->toBe([])
        ->and(missingLocalPhotos($invitation))->toBe([]);
})->with(['wedding', 'muslim_wedding', 'sendoff', 'kitchen_party', 'birthday', 'gala_dinner', 'anniversary', 'save_the_date', 'contribution', 'corporate', 'other']);
