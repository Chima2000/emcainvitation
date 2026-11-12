<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\UserRole;

test('a guest is sent to sign in before the admin dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('a host cannot open the admin dashboard', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('a super admin is sent from the host dashboard to the admin dashboard', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

test('a super admin sees host onboarding instead of the event dashboard', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-bs-theme="light"', false)
        ->assertSee('data-admin-theme="emca"', false)
        ->assertSee(__('Onboard a host'), false)
        ->assertSee(__('Recent hosts'), false)
        ->assertSee(__('Recent events'), false)
        ->assertSee(__('Hosts'), false)
        ->assertSee(route('demos.index'), false)
        ->assertSee('data-bs-target="#logoutConfirmationModal"', false)
        ->assertSee('id="logoutConfirmationModal"', false)
        ->assertSee(route('logout'), false)
        ->assertSee(__('Confirm sign out'), false)
        ->assertSee(__('Are you sure you want to sign out of your account?'), false)
        ->assertSee(__('Stay signed in'), false)
        ->assertDontSee(__('New event'), false)
        ->assertDontSee(route('events.create'), false);
});

test('a super admin onboards hosts from a modal and the standalone create page is gone', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('id="onboardHostModal"', false)
        ->assertSee('data-bs-target="#onboardHostModal"', false)
        ->assertSee(__('1. Account'), false)
        ->assertSee(__('2. Optional location'), false);

    $this->actingAs($admin)
        ->get('/admin/users/create')
        ->assertStatus(404);
});

test('a super admin sees hosts and events on the overview', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['name' => 'Neema Ally']);

    Event::factory()->for($host)->create([
        'title' => 'Harusi ya Neema',
        'type' => 'wedding',
        'host_name' => 'Neema',
        'venue' => 'Arusha',
        'event_date' => now()->addWeek(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Neema Ally')
        ->assertSee('Harusi ya Neema')
        ->assertSee('Arusha')
        ->assertSee(__('Event types'), false)
        ->assertSee(__('Upcoming events'), false);
});

test('the admin dashboard charts platform activity from real records', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create(['type' => 'wedding']);
    Guest::factory()->for($event)->attending()->count(2)->create();
    Guest::factory()->for($event)->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('emca-dashboard.js', false)
        ->assertViewHas('stats', fn (array $stats): bool => $stats['hosts'] === 1
            && $stats['events'] === 1
            && $stats['guests'] === 3
            && $stats['attending_percent'] === 67)
        ->assertViewHas('chart', fn (array $chart): bool => $chart['activity']['series'][0]['data']->last() === 1
            && $chart['activity']['series'][1]['data']->last() === 1
            && $chart['replies']['data']->all() === [2, 1, 0]
            && $chart['types']['data']->all() === [1]);
});

test('a super admin onboards a host who then signs in with the local number', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'first_name' => 'Neema',
        'last_name' => 'Ally',
        'phone' => '+255754111222',
        'email' => 'neema@example.com',
        'password' => 'Secret@12',
        'password_confirmation' => 'Secret@12',
        'country' => 'Tanzania',
        'region' => 'Arusha',
        'district' => 'Meru District',
        'ward' => 'Akheri',
        'street' => 'Nkoaranga Road',
        'role' => UserRole::SuperAdmin->value,
    ])->assertRedirect(route('admin.users.index'));

    $host = User::query()->where('phone', '+255754111222')->first();

    expect($host)->not->toBeNull()
        ->and($host->role)->toBe(UserRole::Host)
        ->and($host->name)->toBe('Neema Ally')
        ->and($host->country)->toBe('Tanzania')
        ->and($host->region)->toBe('Arusha')
        ->and($host->hasCompletedProfile())->toBeTrue();

    $this->post(route('logout'));

    $this->post(route('login'), [
        'phone' => '0754111222',
        'password' => 'Secret@12',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($host);
});

test('an onboarded host can finish their profile after signing in', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'first_name' => 'Neema',
        'last_name' => 'Ally',
        'phone' => '+255754111222',
        'email' => 'neema@example.com',
        'password' => 'Secret@12',
        'password_confirmation' => 'Secret@12',
    ])->assertRedirect(route('admin.users.index'));

    $host = User::query()->where('phone', '+255754111222')->firstOrFail();

    expect($host->profile_completed_at)->toBeNull();

    $this->post(route('logout'));

    $this->post(route('login'), [
        'phone' => '0754111222',
        'password' => 'Secret@12',
    ])->assertRedirect(route('profile.edit'));

    $this->put(route('profile.update'), [
        'country' => 'Tanzania',
        'region' => 'Arusha',
        'district' => 'Meru District',
        'ward' => 'Akheri',
        'street' => 'Nkoaranga Road',
    ])->assertRedirect(route('dashboard'));

    expect($host->fresh()->hasCompletedProfile())->toBeTrue();
});

test('onboarding rejects a phone number that starts with 0', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->post(route('admin.users.store'), [
            'first_name' => 'Neema',
            'last_name' => 'Ally',
            'phone' => '0754111222',
            'email' => 'neema@example.com',
            'password' => 'Secret@12',
            'password_confirmation' => 'Secret@12',
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors([
            'phone' => __('Enter the phone number starting with +255, for example +255692586046.'),
        ]);

    expect(User::query()->where('email', 'neema@example.com')->exists())->toBeFalse();
});

test('the host list escapes the account name', function () {
    $admin = User::factory()->superAdmin()->create();
    User::factory()->create([
        'name' => "Neema <script>alert('x')</script>",
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Neema &lt;script&gt;', false)
        ->assertDontSee("<script>alert('x')</script>", false);
});

test('a super admin changes a host phone and keeps the current password and role', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create([
        'phone' => '+255712345678',
        'password' => 'Secret@12',
    ]);

    $this->actingAs($admin)->put(route('admin.users.update', $host), [
        'first_name' => 'Neema',
        'last_name' => 'Updated',
        'phone' => '+255713333444',
        'email' => 'neema.updated@example.com',
        'password' => '',
        'password_confirmation' => '',
        'role' => UserRole::SuperAdmin->value,
    ])->assertRedirect(route('admin.users.index'));

    $host->refresh();

    expect($host->name)->toBe('Neema Updated')
        ->and($host->phone)->toBe('+255713333444')
        ->and($host->email)->toBe('neema.updated@example.com')
        ->and($host->role)->toBe(UserRole::Host);

    $this->post(route('logout'));

    $this->post(route('login'), [
        'phone' => '0713333444',
        'password' => 'Secret@12',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($host);
});

test('a super admin cannot remove their own account', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();

    $this->assertModelExists($admin);
});

test('a super admin removes a host account', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $host))
        ->assertRedirect(route('admin.users.index'));

    $this->assertModelMissing($host);
});
