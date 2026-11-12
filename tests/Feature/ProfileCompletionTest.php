<?php

use App\Models\User;

test('a guest is sent to sign in before opening profile details', function () {
    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});

test('an incomplete host sees the profile steps and is kept out of the dashboard', function () {
    $host = User::factory()->create(['profile_completed_at' => null]);

    $this->actingAs($host)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee(__('Step 2 of 2'), false)
        ->assertSee(__('Country'), false)
        ->assertSee(__('Region'), false)
        ->assertSee(__('District'), false)
        ->assertSee(__('Ward'), false)
        ->assertSee('data/tanzania-locations.json', false)
        ->assertSee(__('Not listed (enter manually)'), false)
        ->assertSee('profile-edit-page-content', false)
        ->assertSee('profile-edit-page', false)
        ->assertSee('justify-content-center', false);

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertRedirect(route('profile.edit'));
});

test('a host saves a listed location before accessing the dashboard', function () {
    $host = User::factory()->create(['profile_completed_at' => null]);

    $this->actingAs($host)
        ->put(route('profile.update'), [
            'country' => 'Tanzania',
            'region' => 'Arusha',
            'district' => 'Meru District',
            'ward' => 'Akheri',
            'street' => 'Nkoaranga Road',
            'address_line' => 'Near the market',
            'role' => 'super_admin',
        ])
        ->assertRedirect(route('dashboard'));

    $host->refresh();

    expect($host->country)->toBe('Tanzania')
        ->and($host->region)->toBe('Arusha')
        ->and($host->district)->toBe('Meru District')
        ->and($host->ward)->toBe('Akheri')
        ->and($host->street)->toBe('Nkoaranga Road')
        ->and($host->address_line)->toBe('Near the market')
        ->and($host->role->value)->toBe('host')
        ->and($host->hasCompletedProfile())->toBeTrue();

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertOk();
});

test('a host cannot submit a district that belongs to a different region', function () {
    $host = User::factory()->create(['profile_completed_at' => null]);

    $this->actingAs($host)
        ->from(route('profile.edit'))
        ->put(route('profile.update'), [
            'country' => 'Tanzania',
            'region' => 'Arusha',
            'district' => 'Ilala Municipal',
            'ward' => 'Buguruni',
            'street' => 'Nkoaranga Road',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors(['district', 'ward']);

    expect($host->fresh()->profile_completed_at)->toBeNull();
});

test('a host can enter a location manually when it is missing from the list', function () {
    $host = User::factory()->create(['profile_completed_at' => null]);

    $this->actingAs($host)
        ->put(route('profile.update'), [
            'country' => 'Tanzania',
            'region' => '__manual__',
            'region_manual' => 'New Region',
            'district' => '__manual__',
            'district_manual' => 'New District',
            'ward' => '__manual__',
            'ward_manual' => 'New Ward',
            'street' => 'New Street',
        ])
        ->assertRedirect(route('dashboard'));

    expect($host->fresh()->only(['region', 'district', 'ward', 'street']))->toBe([
        'region' => 'New Region',
        'district' => 'New District',
        'ward' => 'New Ward',
        'street' => 'New Street',
    ]);
});

test('a super admin cannot change a host profile through the self-service form', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('profile.edit'))
        ->assertRedirect(route('admin.dashboard'));

    $this->actingAs($admin)
        ->put(route('profile.update'), [
            'country' => 'Tanzania',
            'region' => 'Arusha',
            'district' => 'Meru District',
            'ward' => 'Akheri',
            'street' => 'Nkoaranga Road',
        ])
        ->assertForbidden();
});
