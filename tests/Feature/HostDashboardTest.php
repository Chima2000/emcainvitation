<?php

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Guest;
use App\Models\InvitationTemplate;
use App\Models\User;

test('the dashboard shell uses the Jumuika Pass logo instead of the template logo', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('logo/jumuika-pass.png', false)
        ->assertDontSee('admin/assets/images/emca-logo.svg', false)
        ->assertDontSee('admin/assets/images/logo-text.svg', false);
});

test('both dashboards use the full page width without the right icon rail', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('container-fluid', false)
        ->assertDontSee('app-sidebar-end', false);

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('container-fluid', false)
        ->assertDontSee('app-sidebar-end', false);
});

test('the host panel keeps the host inside the dashboard instead of linking to the public website', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('template-library.index'), false)
        ->assertDontSee('href="'.route('home').'"', false)
        ->assertDontSee('href="'.route('templates.index').'"', false);
});

test('a host browses templates inside the panel and can filter them by category', function () {
    InvitationTemplate::factory()->create(['name' => 'Harusi Gold', 'category' => 'wedding']);
    InvitationTemplate::factory()->create(['name' => 'Birthday Confetti', 'category' => 'birthday']);

    $host = User::factory()->create();

    $this->actingAs($host)
        ->get(route('template-library.index'))
        ->assertOk()
        ->assertSee('class="app-menubar"', false)
        ->assertSee('Harusi Gold')
        ->assertSee('Birthday Confetti')
        ->assertSee(__('Use this card'));

    $this->actingAs($host)
        ->get(route('template-library.index', ['category' => 'wedding']))
        ->assertOk()
        ->assertViewHas('activeCategory', fn (?EventCategory $category): bool => $category?->slug === 'wedding')
        ->assertViewHas('templates', fn ($templates): bool => $templates->pluck('name')->all() === ['Harusi Gold']);
});

test('guests must sign in to browse the panel template library', function () {
    $this->get(route('template-library.index'))->assertRedirect(route('login'));
});

test('a host without events sees empty charts and an invitation to create one', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('emca-dashboard.js', false)
        ->assertSee(__('No events yet'))
        ->assertViewHas('stats', fn (array $stats): bool => $stats['events'] === 0 && $stats['guests'] === 0)
        ->assertViewHas('chart', fn (array $chart): bool => $chart['activity']['series'][0]['data']->sum() === 0
            && $chart['checkIn']['percent'] === 0);
});

test('a host sees charts built from their own guests and replies', function () {
    $host = User::factory()->create();
    $wedding = Event::factory()->for($host)->create([
        'title' => 'Harusi ya Neema',
        'event_date' => now()->addDays(10),
    ]);
    Event::factory()->for(User::factory())->create(['title' => 'Tukio la Mwingine']);
    Guest::factory()->for($wedding)->attending()->count(3)->create();
    Guest::factory()->for($wedding)->create(['rsvp_status' => 'declined', 'checked_in_at' => now()]);
    Guest::factory()->for($wedding)->create();

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Harusi ya Neema')
        ->assertDontSee('Tukio la Mwingine')
        ->assertDontSee(__('Edit event'))
        ->assertViewHas('stats', fn (array $stats): bool => $stats['events'] === 1
            && $stats['guests'] === 5
            && $stats['attending'] === 3
            && $stats['checked_in'] === 1
            && $stats['replied_percent'] === 80)
        ->assertViewHas('trends', fn (array $trends): bool => $trends['guests'] === 100)
        ->assertViewHas('chart', fn (array $chart): bool => $chart['activity']['series'][0]['data']->last() === 5
            && $chart['activity']['series'][1]['data']->last() === 3
            && $chart['activity']['series'][2]['data']->last() === 1
            && $chart['replies']['data']->all() === [3, 1, 1]
            && $chart['checkIn']['percent'] === 20);
});
