<?php

use App\Models\DemoRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Mail;

test('the demo form explains the phone number it can accept', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('placeholder="0692586046"', false)
        ->assertSee(__('Enter 10 digits starting with 0 or 12 digits starting with 255, for example 0692586046 or 255692586046.'));
});

test('a demo request stores a Tanzanian phone number that can open a trial', function () {
    fakeSmsGateway();
    Mail::fake();

    $admin = User::factory()->superAdmin()->create();

    $this->post(route('demos.store'), [
        'name' => 'Neema Ally',
        'email' => 'neema@example.com',
        'phone' => '0712 345 678',
    ])->assertRedirect(route('home').'#demo');

    $demo = DemoRequest::query()->where('email', 'neema@example.com')->first();

    expect($demo)->not->toBeNull()
        ->and($demo->phone)->toBe('+255712345678');

    $this->actingAs($admin)
        ->post(route('demos.trial', $demo), ['days' => 7])
        ->assertRedirect(route('demos.index'))
        ->assertSessionHas('status', __('A :days-day trial is active. The link was sent by SMS and email.', ['days' => 7]));

    expect(User::query()->where('phone', '+255712345678')->where('role', UserRole::Lead)->exists())->toBeTrue();
});

test('a demo request accepts the phone number without a plus sign', function () {
    $this->post(route('demos.store'), [
        'name' => 'Neema Ally',
        'email' => 'neema@example.com',
        'phone' => '255692586046',
    ])->assertRedirect(route('home').'#demo');

    expect(DemoRequest::query()->where('email', 'neema@example.com')->value('phone'))->toBe('+255692586046');
});

test('a demo request rejects a phone number that cannot receive a trial link', function () {
    $this->from(route('home'))
        ->post(route('demos.store'), [
            'name' => 'Neema Ally',
            'email' => 'neema@example.com',
            'phone' => 'call me',
        ])
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors([
            'phone' => __('Enter a Tanzanian phone number as 10 digits starting with 0 or 12 digits starting with 255, for example 0692586046 or 255692586046.'),
        ]);

    expect(DemoRequest::query()->where('email', 'neema@example.com')->exists())->toBeFalse();
});
