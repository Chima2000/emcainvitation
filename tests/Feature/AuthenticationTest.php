<?php

use App\Models\Event;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Hash;

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('the sign-in page shows the photo wall behind the card', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('jumuika-auth-card', false)
        ->assertSee('background: #f5f5f5', false)
        ->assertSee('logo/jumuika-pass.png', false)
        ->assertSee(__('Invite better. Manage better. Spend less.'), false)
        ->assertSee('favicon.svg', false)
        ->assertSee('apple-touch-icon.png', false)
        ->assertDontSee('admin/assets/images/favicon.png', false)
        ->assertSee('jumuika-auth-wall jumuika-wall-login', false)
        ->assertSee('images/home/occasion-wedding.jpg', false)
        ->assertSee('images/occasions/wedding.jpg', false)
        ->assertSee('images/auth/login-15.jpg', false)
        ->assertDontSee('images/auth/login-14.jpg', false)
        ->assertDontSee('images/occasions/gathering.jpg', false)
        ->assertDontSee('border-radius: 22px', false)
        ->assertSee(__('Back to website'), false)
        ->assertSee(route('home'), false)
        ->assertSee('data-bs-theme="light"', false)
        ->assertSee(route('register'), false)
        ->assertSee(__('Reset password'), false)
        ->assertSee('data-auth-flip-to="reset"', false)
        ->assertSee('jumuika-flip-away', false)
        ->assertSee(route('password.sms'), false);
});

test('the registration page shows the photo wall behind the card', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('jumuika-auth-card', false)
        ->assertSee('background: #f5f5f5', false)
        ->assertSee('logo/jumuika-pass.png', false)
        ->assertSee(__('Invite better. Manage better. Spend less.'), false)
        ->assertSee('favicon.svg', false)
        ->assertSee('apple-touch-icon.png', false)
        ->assertDontSee('admin/assets/images/favicon.png', false)
        ->assertSee('jumuika-auth-wall jumuika-wall-register', false)
        ->assertSee('images/occasions/gathering.jpg', false)
        ->assertSee('images/home/occasion-kitchen-party.jpg', false)
        ->assertDontSee('images/auth/register-11.jpg', false)
        ->assertDontSee('images/home/occasion-wedding.jpg', false)
        ->assertSee(__('Back to website'), false)
        ->assertSee('name="first_name"', false)
        ->assertSee('name="last_name"', false)
        ->assertSee('name="phone"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false)
        ->assertSee(__('Repeat password'), false)
        ->assertDontSee(__('Step 1 of 2'), false)
        ->assertDontSee('name="name"', false)
        ->assertSee(route('login'), false);
});

test('a guest registers an account and must complete location details before using the dashboard', function () {
    $response = $this->post(route('register'), [
        'first_name' => 'Amina',
        'last_name' => 'Mwangi',
        'phone' => '+255692586046',
        'email' => 'amina@example.com',
        'password' => 'Secret@12',
        'password_confirmation' => 'Secret@12',
        'role' => 'super_admin',
        'name' => '<script>alert(1)</script>',
    ]);

    $response->assertRedirect(route('profile.edit'));

    $user = User::query()->where('email', 'amina@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->first_name)->toBe('Amina')
        ->and($user->last_name)->toBe('Mwangi')
        ->and($user->name)->toBe('Amina Mwangi')
        ->and($user->phone)->toBe('+255692586046')
        ->and($user->role)->toBe(UserRole::Host)
        ->and($user->profile_completed_at)->toBeNull()
        ->and(Hash::check('Secret@12', $user->password))->toBeTrue();

    $this->assertAuthenticatedAs($user);

    $this->get(route('dashboard'))
        ->assertRedirect(route('profile.edit'));

    $this->put(route('profile.update'), [
        'country' => 'Tanzania',
        'region' => 'Arusha',
        'district' => 'Meru District',
        'ward' => 'Akheri',
        'street' => 'Nkoaranga Road',
        'address_line' => 'Near the market',
    ])->assertRedirect(route('dashboard'));

    $user->refresh();

    expect($user->country)->toBe('Tanzania')
        ->and($user->region)->toBe('Arusha')
        ->and($user->district)->toBe('Meru District')
        ->and($user->ward)->toBe('Akheri')
        ->and($user->street)->toBe('Nkoaranga Road')
        ->and($user->hasCompletedProfile())->toBeTrue();

    $this->get(route('dashboard'))
        ->assertRedirect(route('subscription.index'));

    $this->get(route('subscription.index'))
        ->assertOk()
        ->assertSee('Amina Mwangi', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('registration requires first name, last name, phone, email, and password', function () {
    $this->from(route('register'))
        ->post(route('register'), [])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors([
            'first_name' => 'Sehemu ya jina la kwanza inahitajika.',
            'last_name' => 'Sehemu ya jina la mwisho inahitajika.',
            'phone' => 'Sehemu ya simu inahitajika.',
            'email' => 'Sehemu ya barua pepe inahitajika.',
            'password' => 'Sehemu ya nenosiri inahitajika.',
        ]);

    $this->assertGuest();
});

test('registration stays on the form when the repeated password does not match', function () {
    $this->from(route('register'))
        ->post(route('register'), [
            'first_name' => 'Amina',
            'last_name' => 'Mwangi',
            'phone' => '+255692586046',
            'email' => 'amina@example.com',
            'password' => 'Secret@12',
            'password_confirmation' => 'different',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors([
            'password' => 'Uthibitisho wa nenosiri haulingani.',
        ]);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'amina@example.com']);
});

test('registration normalizes a valid local phone number to +255', function () {
    $this->from(route('register'))
        ->post(route('register'), [
            'first_name' => 'Amina',
            'last_name' => 'Mwangi',
            'phone' => '0712345678',
            'email' => 'amina@example.com',
            'password' => 'Secret@12',
            'password_confirmation' => 'Secret@12',
        ])
        ->assertRedirect(route('profile.edit'));

    $this->assertDatabaseHas('users', [
        'email' => 'amina@example.com',
        'phone' => '+255712345678',
    ]);
});

test('registration rejects a local phone number with an invalid length', function () {
    $this->from(route('register'))
        ->post(route('register'), [
            'first_name' => 'Amina',
            'last_name' => 'Mwangi',
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

test('registration rejects an email address that is already in use', function () {
    User::factory()->create([
        'email' => 'amina@example.com',
        'phone' => '+255712345678',
    ]);

    $this->from(route('register'))
        ->post(route('register'), [
            'first_name' => 'Amina',
            'last_name' => 'Mwangi',
            'phone' => '+255692586046',
            'email' => 'amina@example.com',
            'password' => 'Secret@12',
            'password_confirmation' => 'Secret@12',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors([
            'email' => 'barua pepe tayari imeshatumika.',
        ]);

    $this->assertGuest();
});

test('a host sees the admin panel shell', function () {
    $user = User::factory()->create();
    $event = Event::factory()->for($user)->create([
        'title' => 'Harusi ya Amina',
        'type' => 'wedding',
        'host_name' => 'Amina',
        'venue' => 'Moshi',
        'event_date' => now()->addWeek(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('app-menubar', false)
        ->assertSee('data-bs-theme="light"', false)
        ->assertSee('data-admin-theme="emca"', false)
        ->assertSee('favicon.svg', false)
        ->assertSee('apple-touch-icon.png', false)
        ->assertDontSee('admin/assets/images/favicon.png', false)
        ->assertSee($user->name)
        ->assertSee('Harusi ya Amina')
        ->assertSee(__('All events'), false)
        ->assertSee(__('New event'), false)
        ->assertDontSee('#guests', false)
        ->assertDontSee('#vendors', false)
        ->assertSee(route('template-library.index'), false)
        ->assertDontSee(__('Home'), false)
        ->assertDontSee(__('Features'), false)
        ->assertDontSee(__('Pricing'), false);

    $this->actingAs($user)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('app-page-title', false)
        ->assertSee('Harusi ya Amina')
        ->assertSee('href="#guests"', false)
        ->assertSee('href="#check-in"', false)
        ->assertSee('href="#messages"', false)
        ->assertSee('href="#pledges"', false)
        ->assertSee('href="#budget"', false)
        ->assertSee('href="#tasks"', false)
        ->assertSee('href="#vendors"', false)
        ->assertSee(route('events.show', $event).'#guests', false)
        ->assertSee(route('events.show', $event).'#committees', false)
        ->assertSee(route('events.show', $event).'#vendors', false)
        ->assertSeeInOrder([
            'data-emca-event-link="design"',
            'data-emca-event-link="edit"',
            'data-emca-event-link="check-in"',
            'data-emca-event-link="guests"',
            'data-emca-event-link="committees"',
            'data-emca-event-link="billing"',
            'data-emca-event-link="messages"',
            'data-emca-event-link="pledges"',
            'data-emca-event-link="budget"',
            'data-emca-event-link="tasks"',
            'data-emca-event-link="vendors"',
        ], false)
        ->assertDontSee('menu-link active open', false)
        ->assertDontSee('>Harusi ya Amina</span>', false);

    $this->actingAs($user)
        ->get(route('events.create'))
        ->assertRedirect(route('events.index', ['new' => 1]));

    $this->actingAs($user)
        ->get(route('events.index', ['new' => 1]))
        ->assertOk()
        ->assertDontSee('#guests', false)
        ->assertSee('id="createEventModal"', false)
        ->assertSee('data-bs-target="#createEventModal"', false)
        ->assertSee('name="title"', false);
});
