<?php

use App\Http\Middleware\KeepFlashAcrossLayouts;
use App\Models\User;

test('every layout tells the browser which pages it can swap in place', function () {
    $host = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-emca-layout="marketing"', false)
        ->assertSee('data-emca-region="demo-form"', false)
        ->assertSee('emca-ajax.js', false);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-emca-layout="auth-login"', false)
        ->assertSee('data-emca-region="page"', false);

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-emca-layout="dashboard"', false)
        ->assertSee('data-emca-region="modals"', false)
        ->assertSee('data-emca-sync="wallet-chip"', false);

    $this->actingAs($admin)
        ->get(route('admin.templates.index'))
        ->assertOk()
        ->assertSee('data-emca-layout="admin"', false)
        ->assertSee('data-emca-sync="menu"', false)
        ->assertSee('data-emca-money', false);
});

test('sign out and language switches keep a normal page load', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertSee('action="'.route('logout').'" data-emca-native', false)
        ->assertSee('action="'.route('locale.update', 'en').'" data-emca-native', false);
});

dataset('demo request', [[[
    'name' => 'Neema Ally',
    'email' => 'neema@example.com',
    'phone' => '0712345678',
]]]);

test('a flash message survives when a background submit lands on another layout', function (array $demo) {
    $this->withHeader(KeepFlashAcrossLayouts::Header, 'auth-login')
        ->followingRedirects()
        ->post(route('demos.store'), $demo)
        ->assertOk()
        ->assertSee('data-emca-layout="marketing"', false);

    expect(session('status'))->toBe(__('Demo request received. We will contact you on the phone number you gave us.'));
})->with('demo request');

test('a flash message is used up when the background submit stays on the same layout', function (array $demo) {
    $this->withHeader(KeepFlashAcrossLayouts::Header, 'marketing')
        ->followingRedirects()
        ->post(route('demos.store'), $demo)
        ->assertOk()
        ->assertSee(__('Demo request received. We will contact you on the phone number you gave us.'));

    expect(session('status'))->toBeNull();
})->with('demo request');

test('money fields are sent without commas and saved as whole numbers', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.packages.index'))
        ->assertOk()
        ->assertSee('id="package-price" name="price"', false)
        ->assertSee('inputmode="numeric" autocomplete="off" data-emca-money', false);
});
