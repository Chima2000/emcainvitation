<?php

use App\EventCategoryGroup;
use App\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Guest;
use App\Models\InvitationTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @return array<string, mixed>
 */
function eventPayload(array $overrides = []): array
{
    return [
        'title' => 'Mahafali ya Neema',
        'type' => 'wedding',
        'host_name' => 'Neema',
        'venue' => 'Arusha',
        'event_date' => now()->addMonth()->format('Y-m-d H:i'),
        ...$overrides,
    ];
}

test('the template pills come from the configured categories', function () {
    $admin = User::factory()->superAdmin()->create();
    EventCategory::factory()->create(['slug' => 'graduation', 'name' => 'Graduation']);
    InvitationTemplate::factory()->count(2)->create(['category' => 'graduation']);

    $this->actingAs($admin)
        ->get(route('admin.templates.index'))
        ->assertOk()
        ->assertSee(route('admin.templates.index', ['category' => 'graduation']), false)
        ->assertSee('emca-filter-pills', false)
        ->assertSee('id="template-categories"', false)
        ->assertSee('fa-solid', false)
        ->assertSee(__('Weddings and milestones'), false)
        ->assertDontSee('emca-category-choice', false)
        ->assertSee('Graduation')
        ->assertSee(route('admin.event-categories.index'), false)
        ->assertViewHas('templateCounts', fn ($counts): bool => $counts->get('graduation') === 2);

    $this->actingAs($admin)
        ->get(route('admin.templates.index', ['category' => 'graduation']))
        ->assertOk()
        ->assertViewHas('category', fn (?EventCategory $category): bool => $category?->slug === 'graduation')
        ->assertViewHas('templates', fn ($templates): bool => $templates->total() === 2);
});

test('a super admin creates a category with a fixed slug and a Swahili name', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.event-categories.index'))
        ->assertOk()
        ->assertSee('id="categoryModal"', false);

    $this->actingAs($admin)
        ->post(route('admin.event-categories.store'), [
            'name' => 'Graduation Party',
            'name_sw' => 'Mahafali',
            'menu_group' => 'festivals',
            'icon' => 'fa-graduation-cap',
            'photo' => 'celebration.jpg',
            'sort_order' => 120,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.event-categories.index'))
        ->assertSessionHasNoErrors();

    $category = EventCategory::query()->where('name', 'Graduation Party')->sole();

    expect($category->slug)->toBe('graduation_party')
        ->and($category->icon)->toBe('fa-graduation-cap')
        ->and($category->menu_group)->toBe(EventCategoryGroup::Festivals)
        ->and($category->is_active)->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.event-categories.index'))
        ->assertOk()
        ->assertSee(__('Group'), false)
        ->assertSee(__('Festivals'), false);

    $this->actingAs($admin)
        ->get(route('admin.templates.index'))
        ->assertOk()
        ->assertSeeInOrder([__('Festivals'), 'Mahafali', __('Gatherings')], false);

    $this->actingAs($admin)
        ->put(route('admin.event-categories.update', $category), [
            'name' => 'Graduations',
            'name_sw' => 'Mahafali',
            'menu_group' => 'work',
            'icon' => 'fa-award',
            'photo' => 'birthday.jpg',
            'sort_order' => 5,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.event-categories.index'));

    expect($category->refresh())
        ->slug->toBe('graduation_party')
        ->name->toBe('Graduations')
        ->menu_group->toBe(EventCategoryGroup::Work)
        ->photo->toBe('birthday.jpg');

    app()->setLocale('sw');
    expect($category->label())->toBe('Mahafali');

    app()->setLocale('en');
    expect($category->label())->toBe('Graduations');
});

test('other stays at the bottom when a new category has a higher order', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.event-categories.store'), [
            'name' => 'Graduation',
            'menu_group' => 'gatherings',
            'photo' => 'celebration.jpg',
            'sort_order' => 120,
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get(route('admin.event-categories.index'))
        ->assertOk()
        ->assertSeeInOrder(['Corporate', 'Graduation', 'Other'])
        ->assertViewHas('categories', fn ($categories): bool => $categories->last()?->slug === 'other');
});

test('a second category with a similar name gets its own slug', function () {
    $admin = User::factory()->superAdmin()->create();
    EventCategory::factory()->create(['slug' => 'graduation', 'name' => 'Graduation']);

    $this->actingAs($admin)
        ->post(route('admin.event-categories.store'), [
            'name' => 'Graduation!',
            'menu_group' => 'gatherings',
            'photo' => 'celebration.jpg',
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect(EventCategory::query()->where('name', 'Graduation!')->value('slug'))->toBe('graduation_2');
});

test('an invalid category keeps the popup open with the errors', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->from(route('admin.event-categories.index'))
        ->post(route('admin.event-categories.store'), [
            'name' => 'Weddings',
            'icon' => 'fa-not-real',
            'photo' => '../secret.jpg',
        ])
        ->assertRedirect(route('admin.event-categories.index'))
        ->assertSessionHasErrors(['name', 'icon', 'photo'], errorBag: 'category');

    $this->actingAs($admin)
        ->from(route('admin.event-categories.index'))
        ->followingRedirects()
        ->post(route('admin.event-categories.store'), ['name' => '', 'photo' => 'celebration.jpg'])
        ->assertOk()
        ->assertSee('data-emca-open="1"', false);
});

test('a category picture can be uploaded and removed', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $category = EventCategory::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.event-categories.update', $category), [
            'name' => $category->name,
            'menu_group' => 'gatherings',
            'photo' => 'celebration.jpg',
            'photo_upload' => UploadedFile::fake()->image('graduation.jpg', 1200, 600),
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    $path = $category->refresh()->photo_path;

    Storage::disk('public')->assertExists($path);
    expect($category->photoUrl())->toStartWith('/storage/')->toContain($path);

    $this->actingAs($admin)
        ->put(route('admin.event-categories.update', $category), [
            'name' => $category->name,
            'menu_group' => 'gatherings',
            'photo' => 'celebration.jpg',
            'remove_photo' => '1',
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($path);
    expect($category->refresh()->photo_path)->toBeNull()
        ->and($category->photoUrl())->toEndWith('images/occasions/celebration.jpg');
});

test('a switched-off category disappears from host pickers but existing events keep it', function () {
    $category = EventCategory::factory()->create(['slug' => 'graduation', 'name' => 'Graduation']);
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create(['type' => 'graduation', 'status' => EventStatus::Draft]);

    $category->update(['is_active' => false]);

    $this->actingAs($host)
        ->get(route('template-library.index'))
        ->assertOk()
        ->assertDontSee(route('template-library.index', ['category' => 'graduation']), false);

    $this->get(route('templates.index'))
        ->assertDontSee(route('templates.index', ['category' => 'graduation']), false);

    $this->actingAs($host)
        ->post(route('events.store'), eventPayload(['type' => 'graduation']))
        ->assertSessionHasErrors('type', errorBag: 'createEvent');

    $this->actingAs($host)
        ->put(route('events.update', $event), eventPayload(['type' => 'graduation', 'status' => EventStatus::Draft->value]))
        ->assertSessionHasNoErrors('createEvent');

    expect($event->refresh()->type->slug)->toBe('graduation')
        ->and($event->type->label())->toBe('Graduation');
});

test('a host creates an event in an admin-made category', function () {
    EventCategory::factory()->create(['slug' => 'graduation', 'name' => 'Graduation']);
    $host = User::factory()->create();

    $this->actingAs($host)
        ->post(route('events.store'), eventPayload(['type' => 'graduation']))
        ->assertSessionHasNoErrors('createEvent');

    expect($host->events()->sole()->type->slug)->toBe('graduation');
});

test('the event creation modal shows a live card preview and readonly host profile name', function () {
    $host = User::factory()->create(['name' => 'Neema Mushi']);

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-event-preview-form', false)
        ->assertSee('data-preview-card', false)
        ->assertSee('value="Neema Mushi" readonly', false)
        ->assertSee('data-organization-host-toggle', false)
        ->assertSee('data-add-dress-code-color', false)
        ->assertSee('emca-dress-code-color', false)
        ->assertSee('data-template-select-search', false)
        ->assertSee('data-template-menu-toggle', false)
        ->assertSee('data-template-results', false)
        ->assertSee('data-template-results hidden', false)
        ->assertSee('emca-template-results', false)
        ->assertSee(__('Dress code colors'))
        ->assertSee(__('Search templates'))
        ->assertSee(__('Live invitation preview'));

    $response = $this->get(route('dashboard'));
    $html = $response->getContent();

    expect(strpos($html, 'id="create-event-status"'))
        ->toBeGreaterThan(strpos($html, 'id="create-event-is_public"'));
});

test('creating an event uses the host profile name and saves selected dress code colors', function () {
    $host = User::factory()->create(['name' => 'Neema Mushi']);

    $response = $this->actingAs($host)
        ->post(route('events.store'), eventPayload([
            'host_name' => 'Forged Host Name',
            'host_name_is_organization' => '0',
            'dress_code_colors' => ['#B80000', '#153A2D'],
        ]));
    $event = $host->events()->sole();

    $response->assertRedirect(route('events.show', $event));
    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'host_name' => 'Neema Mushi',
        'host_name_is_organization' => false,
        'dress_code_colors' => json_encode(['#B80000', '#153A2D']),
    ]);

    $guest = Guest::factory()->for($event)->create(['name' => 'Guest Name']);

    $this->get(route('invitations.show', $guest->token))
        ->assertOk()
        ->assertSee('Neema Mushi')
        ->assertSee('background-color: #B80000', false)
        ->assertSee('background-color: #153A2D', false);
});

test('a host can choose an organization name for the invitation', function () {
    $host = User::factory()->create(['name' => 'Neema Mushi']);

    $response = $this->actingAs($host)
        ->post(route('events.store'), eventPayload([
            'host_name_is_organization' => '1',
            'organization_name' => 'Upendo Foundation',
        ]));
    $event = $host->events()->sole();

    $response->assertRedirect(route('events.show', $event));
    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'host_name' => 'Upendo Foundation',
        'host_name_is_organization' => true,
    ]);

    expect($event->hostsLabel())->toBe('Upendo Foundation');
});

test('updating an event can switch its displayed host back to the profile name', function () {
    $host = User::factory()->create(['name' => 'Neema Mushi']);
    $event = Event::factory()->for($host)->create([
        'host_name' => 'Upendo Foundation',
        'host_name_is_organization' => true,
    ]);

    $this->actingAs($host)
        ->put(route('events.update', $event), eventPayload([
            'host_name' => 'Forged Host Name',
            'host_name_is_organization' => '0',
            'organization_name' => 'Ignored Organisation',
            'status' => EventStatus::Draft->value,
        ]))
        ->assertRedirect(route('events.show', $event));

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'host_name' => 'Neema Mushi',
        'host_name_is_organization' => false,
    ]);
});

test('a host cannot enable organization display without supplying an organization name', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->from(route('dashboard'))
        ->post(route('events.store'), eventPayload([
            'host_name_is_organization' => '1',
            'organization_name' => '',
        ]))
        ->assertRedirect(route('events.index', ['new' => 1]))
        ->assertSessionHasErrors('organization_name', errorBag: 'createEvent');

    $this->assertDatabaseCount('events', 0);
});

test('a host cannot save invalid dress code color values', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->from(route('dashboard'))
        ->post(route('events.store'), eventPayload([
            'dress_code_colors' => ['red; background:url(javascript:alert(1))'],
        ]))
        ->assertRedirect(route('events.index', ['new' => 1]))
        ->assertSessionHasErrors([
            'dress_code_colors.0' => __('validation.regex', ['attribute' => 'dress_code_colors.0']),
        ], errorBag: 'createEvent');

    $this->assertDatabaseCount('events', 0);
});

test('deleting a category in use switches it off instead', function () {
    $admin = User::factory()->superAdmin()->create();
    $category = EventCategory::factory()->create(['slug' => 'graduation']);
    InvitationTemplate::factory()->create(['category' => 'graduation']);

    $this->actingAs($admin)
        ->delete(route('admin.event-categories.destroy', $category))
        ->assertRedirect(route('admin.event-categories.index'));

    expect($category->refresh()->is_active)->toBeFalse();

    $unused = EventCategory::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.event-categories.destroy', $unused))
        ->assertRedirect(route('admin.event-categories.index'));

    expect(EventCategory::query()->whereKey($unused->id)->exists())->toBeFalse();
});

test('a host cannot manage event categories', function () {
    $host = User::factory()->create();
    $category = EventCategory::query()->where('slug', 'wedding')->sole();

    $this->actingAs($host)->get(route('admin.event-categories.index'))->assertForbidden();
    $this->actingAs($host)->post(route('admin.event-categories.store'), ['name' => 'Sneaky', 'photo' => 'celebration.jpg'])->assertForbidden();
    $this->actingAs($host)->delete(route('admin.event-categories.destroy', $category))->assertForbidden();

    expect(EventCategory::query()->where('name', 'Sneaky')->exists())->toBeFalse()
        ->and($category->refresh()->exists)->toBeTrue();
});

test('a host cannot schedule an event before today', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->from(route('events.index'))
        ->post(route('events.store'), eventPayload([
            'event_date' => now()->subDay()->format('Y-m-d H:i'),
        ]))
        ->assertRedirect(route('events.index', ['new' => 1]))
        ->assertSessionHasErrors('event_date', errorBag: 'createEvent');

    $this->assertDatabaseCount('events', 0);
});

test('the event date calendar starts at the beginning of today', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->get(route('events.edit', $event))
        ->assertOk()
        ->assertSee('min="'.now()->timezone(config('app.timezone'))->format('Y-m-d\T00:00').'"', false);
});

test('updating an event rejects a new date before today but keeps a date that is already past', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create([
        'event_date' => now()->subDays(3),
    ]);
    $storedDate = $event->event_date->timezone(config('app.timezone'))->format('Y-m-d H:i');

    $this->actingAs($host)
        ->from(route('events.edit', $event))
        ->put(route('events.update', $event), eventPayload([
            'status' => $event->status->value,
            'event_date' => now()->subDay()->format('Y-m-d H:i'),
        ]))
        ->assertRedirect(route('events.edit', $event))
        ->assertSessionHasErrors([
            'event_date' => __('Choose today or a later date.'),
        ]);

    $this->put(route('events.update', $event), eventPayload([
        'title' => 'Mahafali iliyopita',
        'status' => $event->status->value,
        'event_date' => $storedDate,
    ]))->assertRedirect(route('events.show', $event));

    expect($event->fresh()->title)->toBe('Mahafali iliyopita');
});
