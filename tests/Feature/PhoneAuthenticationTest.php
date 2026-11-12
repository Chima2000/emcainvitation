<?php

use App\Models\User;
use App\UserRole;
use Database\Seeders\SuperAdminSeeder;

test('the sign-in form asks for a phone number that starts with 0', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('name="phone"', false)
        ->assertSee('jumuika-phone-flag', false)
        ->assertSee('0692586046', false)
        ->assertSee(__('Enter the number you use to sign in. It starts with 0, for example 0692586046.'), false)
        ->assertSee('data-password-toggle', false)
        ->assertSee(__('Show password'), false)
        ->assertDontSee('name="email"', false);

    expect(substr_count($response->getContent(), 'class="jumuika-phone-flag"'))->toBe(2)
        ->and(substr_count($response->getContent(), 'class="jumuika-password-toggle"'))->toBe(1);
});

test('the registration form starts the phone number with +255', function () {
    $response = $this->get(route('register'));

    $response->assertOk()
        ->assertSee('name="first_name"', false)
        ->assertSee('name="last_name"', false)
        ->assertSee('value="+255"', false)
        ->assertSee('jumuika-phone-flag', false)
        ->assertSee('fill="#09f"', false)
        ->assertSee('data-password-toggle', false)
        ->assertSee('name="password_confirmation"', false)
        ->assertSee(__('Show password'), false)
        ->assertSee('+255692586046', false)
        ->assertSee(__('Enter 10 digits starting with 0 or 12 digits starting with 255, for example 0692586046 or 255692586046.'), false)
        ->assertSee('name="email"', false);

    expect(substr_count($response->getContent(), 'class="jumuika-password-toggle"'))->toBe(2);
});

test('registration stores a host and ignores a submitted role', function () {
    $this->post(route('register'), [
        'first_name' => 'Amina',
        'last_name' => 'Juma',
        'phone' => '+255 712 345 678',
        'email' => 'amina@example.com',
        'password' => 'Secret@12',
        'password_confirmation' => 'Secret@12',
        'role' => UserRole::SuperAdmin->value,
    ])->assertRedirect(route('profile.edit'));

    $user = User::query()->where('phone', '+255712345678')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Host)
        ->and($user->name)->toBe('Amina Juma')
        ->and($user->email)->toBe('amina@example.com');

    $this->assertAuthenticatedAs($user);
});

test('registration rejects a phone number with an invalid length', function () {
    $this->from(route('register'))
        ->post(route('register'), [
            'first_name' => 'Amina',
            'last_name' => 'Juma',
            'phone' => '071234567',
            'email' => 'amina@example.com',
            'password' => 'Secret@12',
            'password_confirmation' => 'Secret@12',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors([
            'phone' => __('Enter a Tanzanian phone number as 10 digits starting with 0 or 12 digits starting with 255, for example 0692586046 or 255692586046.'),
        ]);

    $this->assertGuest();
});

test('a host signs in with the local number and the password used at registration', function () {
    $this->post(route('register'), [
        'first_name' => 'Amina',
        'last_name' => 'Juma',
        'phone' => '+255712345678',
        'email' => 'amina@example.com',
        'password' => 'Secret@12',
        'password_confirmation' => 'Secret@12',
    ]);

    $user = User::query()->where('phone', '+255712345678')->firstOrFail();

    $this->post(route('logout'));

    $this->post(route('login'), [
        'phone' => '0712345678',
        'password' => 'Secret@12',
    ])->assertRedirect(route('profile.edit'));

    $this->assertAuthenticatedAs($user);
});

test('sign-in rejects the international number', function () {
    User::factory()->create([
        'phone' => '+255712345678',
        'password' => 'Secret@12',
    ]);

    $this->from(route('login'))
        ->post(route('login'), [
            'phone' => '+255712345678',
            'password' => 'Secret@12',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'phone' => __('Enter the phone number starting with 0, for example 0692586046.'),
        ]);

    $this->assertGuest();
});

test('sign-in rejects a wrong password', function () {
    User::factory()->create([
        'phone' => '+255712345678',
        'password' => 'Secret@12',
    ]);

    $this->from(route('login'))
        ->post(route('login'), [
            'phone' => '0712345678',
            'password' => 'Wrong@12',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'phone' => __('These credentials do not match our records.'),
        ]);

    $this->assertGuest();
});

test('the seeded super admin signs in with 0692586046 and opens the admin dashboard', function () {
    $this->seed(SuperAdminSeeder::class);

    $this->post(route('login'), [
        'phone' => '0692586046',
        'password' => 'Chima@97!',
    ])->assertRedirect(route('admin.dashboard'));

    $admin = User::query()->where('phone', '+255692586046')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->isSuperAdmin())->toBeTrue()
        ->and($admin->name)->toBe('Super Admin');

    $this->assertAuthenticatedAs($admin);
});
