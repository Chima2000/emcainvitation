<?php

use App\Models\Setting;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Http;

/**
 * @return array<string, string>
 */
function hostRegistrationDetails(): array
{
    return [
        'first_name' => 'Amina',
        'last_name' => 'Juma',
        'phone' => '+255712345678',
        'email' => 'amina@example.com',
        'password' => 'Secret@12',
        'password_confirmation' => 'Secret@12',
    ];
}

test('registration sends an SMS code and creates the account only after the code is entered', function () {
    fakeSmsGateway();
    config(['queue.default' => 'database']);

    $this->post(route('register'), hostRegistrationDetails())
        ->assertRedirect(route('register.verify'));

    expect(User::query()->count())->toBe(0)
        ->and(lastSentSms()['to'])->toBe('255712345678')
        ->and(lastSentSms()['from'])->toBe('JUMUIKA');

    $this->get(route('register.verify'))
        ->assertOk()
        ->assertSee('0712345678')
        ->assertSee('name="code"', false);

    $this->post(route('register.verify'), ['code' => lastSentSmsCode()])
        ->assertRedirect(route('profile.edit'));

    $user = User::query()->sole();

    expect($user->phone)->toBe('+255712345678')
        ->and($user->role)->toBe(UserRole::Host)
        ->and($user->hasVerifiedPhone())->toBeTrue();

    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'));
    $this->post(route('login'), ['phone' => '0712345678', 'password' => 'Secret@12']);

    $this->assertAuthenticatedAs($user);
});

test('registration converts a local phone number to the international SMS number', function () {
    fakeSmsGateway();
    $details = hostRegistrationDetails();
    $details['phone'] = '0692586046';

    $this->post(route('register'), $details)
        ->assertRedirect(route('register.verify'));

    expect(lastSentSms()['to'])->toBe('255692586046');

    $this->get(route('register.verify'))
        ->assertOk()
        ->assertSee('0692586046');
});

test('registration accepts the country code without a plus sign', function () {
    fakeSmsGateway();
    $details = hostRegistrationDetails();
    $details['phone'] = '255712345678';

    $this->post(route('register'), $details)
        ->assertRedirect(route('register.verify'));

    expect(lastSentSms()['to'])->toBe('255712345678');
});

test('a wrong SMS code does not create the account', function () {
    fakeSmsGateway();

    $this->post(route('register'), hostRegistrationDetails());
    $wrongCode = lastSentSmsCode() === '000000' ? '111111' : '000000';

    $this->from(route('register.verify'))
        ->post(route('register.verify'), ['code' => $wrongCode])
        ->assertRedirect(route('register.verify'))
        ->assertSessionHasErrors('code');

    expect(User::query()->count())->toBe(0);
    $this->assertGuest();
});

test('the code stops working after five wrong attempts', function () {
    fakeSmsGateway();

    $this->post(route('register'), hostRegistrationDetails());
    $code = lastSentSmsCode();
    $wrongCode = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        $this->post(route('register.verify'), ['code' => $wrongCode]);
    }

    $this->post(route('register.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');

    expect(User::query()->count())->toBe(0);
});

test('the admin message template is used for the registration code', function () {
    fakeSmsGateway();
    Setting::store(['messages.registration_code' => 'Code yako ni {code} kwa {app}, dakika {minutes}.', 'otp.lifetime' => '7']);

    $this->post(route('register'), hostRegistrationDetails());

    expect(lastSentSms()['text'])->toBe('Code yako ni '.lastSentSmsCode().' kwa '.config('app.name').', dakika 7.');
});

test('registration works straight away when the admin turns phone verification off', function () {
    fakeSmsGateway();
    Setting::store(['registration.verify_phone' => '0']);

    $this->post(route('register'), hostRegistrationDetails())
        ->assertRedirect(route('profile.edit'));

    $user = User::query()->sole();

    expect($user->hasVerifiedPhone())->toBeFalse();
    $this->assertAuthenticatedAs($user);
    Http::assertNothingSent();
});

test('registration keeps the form when the SMS cannot be delivered', function () {
    fakeSmsGateway(status: 500);

    $this->from(route('register'))
        ->post(route('register'), hostRegistrationDetails())
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('phone')
        ->assertSessionHasInput('email', 'amina@example.com')
        ->assertSessionMissing('pending_registration');

    expect(User::query()->count())->toBe(0);
});

test('a new code can be requested once a minute and replaces the old one', function () {
    fakeSmsGateway();

    $this->post(route('register'), hostRegistrationDetails());
    $firstCode = lastSentSmsCode();

    $this->from(route('register.verify'))
        ->post(route('register.verify.resend'))
        ->assertSessionHasNoErrors();

    $secondCode = lastSentSmsCode();

    $this->from(route('register.verify'))
        ->post(route('register.verify.resend'))
        ->assertSessionHasErrors('code');

    Http::assertSentCount(2);

    if ($firstCode !== $secondCode) {
        $this->post(route('register.verify'), ['code' => $firstCode])->assertSessionHasErrors('code');
    }

    $this->post(route('register.verify'), ['code' => $secondCode])->assertRedirect(route('profile.edit'));
});

test('a failed resend can be retried without waiting for the resend limit', function () {
    config([
        'services.sms.base_url' => 'https://sms.test/send',
        'services.sms.username' => 'emca',
        'services.sms.password' => 'secret',
        'services.sms.from' => 'JUMUIKA',
    ]);

    $smsAttempt = 0;
    Http::fake(['sms.test/*' => function () use (&$smsAttempt) {
        $smsAttempt++;

        return Http::response($smsAttempt === 2 ? 'Rejected' : 'OK', $smsAttempt === 2 ? 500 : 200);
    }]);

    $this->post(route('register'), hostRegistrationDetails())
        ->assertRedirect(route('register.verify'));

    $this->from(route('register.verify'))
        ->post(route('register.verify.resend'))
        ->assertSessionHasErrors('code');

    $this->from(route('register.verify'))
        ->post(route('register.verify.resend'))
        ->assertSessionHasNoErrors();

    Http::assertSentCount(3);
});

test('the verify page sends visitors without a pending registration back to register', function () {
    $this->get(route('register.verify'))->assertRedirect(route('register'));
    $this->post(route('register.verify'), ['code' => '123456'])->assertRedirect(route('register'));
});
