<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('the sign-in page posts the reset form to the SMS reset route', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('action="'.route('password.sms').'"', false)
        ->assertSee('name="reset_phone"', false)
        ->assertDontSee('data-auth-reset-form', false);
});

test('a host resets the password with the code sent to the registered number', function () {
    fakeSmsGateway();
    $user = User::factory()->create([
        'first_name' => 'Rehema',
        'phone' => '+255712345678',
        'password' => 'Old@1234',
        'remember_token' => 'remember-me',
    ]);

    $this->post(route('password.sms'), ['reset_phone' => '0712345678'])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHasNoErrors();

    expect(lastSentSms()['to'])->toBe('255712345678')
        ->and(lastSentSms()['text'])->toContain('Rehema');

    $this->get(route('password.reset'))
        ->assertOk()
        ->assertSee('0712***678')
        ->assertSee('name="password_confirmation"', false);

    $this->post(route('password.update'), [
        'code' => lastSentSmsCode(),
        'password' => 'New@12345',
        'password_confirmation' => 'New@12345',
    ])->assertRedirect(route('login'));

    expect($user->fresh()->remember_token)->toBeNull();

    $this->post(route('login'), ['phone' => '0712345678', 'password' => 'Old@1234'])
        ->assertSessionHasErrors('phone');
    $this->assertGuest();

    $this->post(route('login'), ['phone' => '0712345678', 'password' => 'New@12345']);
    $this->assertAuthenticatedAs($user);
});

test('a wrong reset code leaves the password unchanged', function () {
    fakeSmsGateway();
    $user = User::factory()->create(['phone' => '+255712345678', 'password' => 'Old@1234']);

    $this->post(route('password.sms'), ['reset_phone' => '0712345678']);
    $wrongCode = lastSentSmsCode() === '000000' ? '111111' : '000000';

    $this->from(route('password.reset'))
        ->post(route('password.update'), [
            'code' => $wrongCode,
            'password' => 'New@12345',
            'password_confirmation' => 'New@12345',
        ])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHasErrors('code');

    $this->post(route('login'), ['phone' => '0712345678', 'password' => 'Old@1234']);
    $this->assertAuthenticatedAs($user);
});

test('an unknown number gets an error on the reset side of the sign-in card', function () {
    fakeSmsGateway();

    $this->from(route('login'))
        ->followingRedirects()
        ->post(route('password.sms'), ['reset_phone' => '0799000111'])
        ->assertOk()
        ->assertSee(__('No account uses this phone number.'))
        ->assertSee('jumuika-flip-rotor is-reset', false);

    Http::assertNothingSent();
});

test('the reset form rejects a number in the wrong format', function () {
    fakeSmsGateway();

    $this->post(route('password.sms'), ['reset_phone' => '+255712345678'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('reset_phone');

    Http::assertNothingSent();
});

test('a reset code can only be requested once a minute', function () {
    fakeSmsGateway();
    User::factory()->create(['phone' => '+255712345678']);

    $this->post(route('password.sms'), ['reset_phone' => '0712345678'])->assertSessionHasNoErrors();
    $this->post(route('password.sms'), ['reset_phone' => '0712345678'])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHasErrors('code');

    Http::assertSentCount(1);
});

test('password reset explains the problem when SMS is not set up', function () {
    User::factory()->create(['phone' => '+255712345678']);

    $this->post(route('password.sms'), ['reset_phone' => '0712345678'])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('reset_phone');

    Http::assertNothingSent();
});

test('the reset page needs a code request first', function () {
    $this->get(route('password.reset'))->assertRedirect(route('login'));
});

test('the admin password reset template is used', function () {
    fakeSmsGateway();
    Setting::store(['messages.password_reset_code' => '{name}: {code}']);
    User::factory()->create(['first_name' => 'Baraka', 'phone' => '+255712345678']);

    $this->post(route('password.sms'), ['reset_phone' => '0712345678']);

    expect(lastSentSms()['text'])->toBe('Baraka: '.lastSentSmsCode());
});
