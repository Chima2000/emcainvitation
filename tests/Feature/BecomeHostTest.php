<?php

use App\Models\Event;
use App\Models\OutboundMessage;
use App\Models\User;
use App\SubscriptionPeriod;
use App\UserRole;
use Illuminate\Support\Facades\Hash;

test('a lead prepares an event but cannot pay, send invitations, or check guests in', function () {
    $lead = User::factory()->create(['role' => UserRole::Lead]);
    $event = Event::factory()->for($lead)->create();

    $this->actingAs($lead)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Become a host'))
        ->assertDontSee(route('wallet.index'), false);

    $this->actingAs($lead)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee(__('This stays locked during a trial'))
        ->assertDontSee(route('events.check-ins.store', $event), false);

    $this->actingAs($lead)
        ->post(route('events.invitations.send', $event), ['channel' => 'sms'])
        ->assertRedirect(route('become-host.create'));

    $this->actingAs($lead)
        ->get(route('subscription.index'))
        ->assertRedirect(route('become-host.create'));

    expect($lead->refresh()->role)->toBe(UserRole::Lead)
        ->and(OutboundMessage::query()->count())->toBe(0);
});

test('an ended trial sends the lead to registration instead of payment', function () {
    $lead = User::factory()->subscriptionExpired()->create(['role' => UserRole::Lead]);

    $this->actingAs($lead)
        ->get(route('dashboard'))
        ->assertRedirect(route('become-host.create'))
        ->assertSessionHas('status', __('Your trial has ended. Register as a host, then choose a subscription.'));
});

test('a lead becomes a host by repeating registration and keeps the trial', function () {
    $this->freezeSecond();
    fakeSmsGateway();
    $endsAt = now()->addDays(6);
    $lead = User::factory()->create([
        'role' => UserRole::Lead,
        'first_name' => 'Neema',
        'last_name' => 'Ally',
        'phone' => '+255712000111',
        'email' => 'neema@example.com',
        'subscription_ends_at' => $endsAt,
    ]);

    $this->actingAs($lead)
        ->post(route('become-host.store'), [
            'first_name' => 'Neema',
            'last_name' => 'Ally',
            'phone' => '0712000111',
            'email' => 'neema@example.com',
            'password' => 'Secret@12',
            'password_confirmation' => 'Secret@12',
        ])
        ->assertRedirect(route('become-host.verify'));

    expect($lead->refresh()->role)->toBe(UserRole::Lead)
        ->and(lastSentSms()['to'])->toBe('255712000111');

    $this->actingAs($lead)
        ->from(route('become-host.verify'))
        ->post(route('become-host.verify.store'), ['code' => '000000'])
        ->assertRedirect(route('become-host.verify'))
        ->assertSessionHasErrors('code');

    $this->actingAs($lead)
        ->post(route('become-host.verify.store'), ['code' => lastSentSmsCode()])
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHas('status', __('You are now a host. Choose a subscription, then sign in.'));

    $this->assertAuthenticatedAs($lead);

    $lead->refresh();

    expect($lead->role)->toBe(UserRole::Host)
        ->and($lead->subscription_ends_at->equalTo($endsAt))->toBeTrue()
        ->and(Hash::check('Secret@12', $lead->password))->toBeTrue();

    $this->actingAs($lead)
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.index'));
});

test('a lead becomes a host immediately when phone verification is off', function () {
    $this->freezeSecond();
    config([
        'services.sms.base_url' => '',
        'services.sms.username' => '',
        'services.sms.password' => '',
    ]);

    $lead = User::factory()->withoutSubscription()->create([
        'role' => UserRole::Lead,
        'phone' => '+255713000222',
        'email' => 'baraka@example.com',
        'wallet_balance' => 20000,
    ]);

    $this->actingAs($lead)
        ->post(route('become-host.store'), [
            'first_name' => 'Baraka',
            'last_name' => 'Mushi',
            'phone' => '0713000222',
            'email' => 'baraka@example.com',
            'password' => 'Secret@12',
            'password_confirmation' => 'Secret@12',
        ])
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHas('status', __('You are now a host. Choose a subscription, then sign in.'));

    $this->actingAs($lead)
        ->post(route('subscription.store'), ['period' => SubscriptionPeriod::Month->value])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('Thank you! Your subscription is active until :date. Sign in with the phone number and password you just chose.', [
            'date' => now()->addMonthNoOverflow()->timezone(config('app.timezone'))->translatedFormat('j F Y'),
        ]));

    $this->assertGuest();

    expect($lead->refresh()->role)->toBe(UserRole::Host);
});

test('becoming a host without a location starts the location step', function () {
    config([
        'services.sms.base_url' => '',
        'services.sms.username' => '',
        'services.sms.password' => '',
    ]);

    $lead = User::factory()->create([
        'role' => UserRole::Lead,
        'profile_completed_at' => null,
        'phone' => '+255714000444',
        'email' => 'tino@example.com',
    ]);

    $this->actingAs($lead)
        ->post(route('become-host.store'), [
            'first_name' => 'Tino',
            'last_name' => 'Mushi',
            'phone' => '0714000444',
            'email' => 'tino@example.com',
            'password' => 'Secret@12',
            'password_confirmation' => 'Secret@12',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', __('You are now a host. Complete your location details, then choose a subscription.'));

    expect($lead->refresh()->role)->toBe(UserRole::Host)
        ->and($lead->hasCompletedProfile())->toBeFalse();

    $this->actingAs($lead)
        ->get(route('dashboard'))
        ->assertRedirect(route('profile.edit'));

    $this->actingAs($lead)
        ->put(route('profile.update'), [
            'country' => 'Tanzania',
            'region' => 'Arusha',
            'district' => 'Meru District',
            'ward' => 'Akheri',
            'street' => 'Nkoaranga Road',
        ])
        ->assertRedirect(route('subscription.index'));
});

test('a host cannot open the lead registration form', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->get(route('become-host.create'))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($host)
        ->post(route('become-host.store'), [
            'first_name' => 'Asha',
            'last_name' => 'Juma',
            'phone' => $host->localPhone(),
            'email' => $host->email,
            'password' => 'Secret@12',
            'password_confirmation' => 'Secret@12',
        ])
        ->assertForbidden();
});
