<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\Http;

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function smsSettingsForm(array $overrides = []): array
{
    return [
        'sms_base_url' => 'https://gateway.test/sms',
        'sms_username' => 'jumuika',
        'sms_password' => 'gateway-secret',
        'sms_sender_id' => 'JUMUIKA',
        'sms_verify_ssl' => '1',
        'registration_verify_phone' => '1',
        'code_lifetime' => '15',
        'registration_message' => 'Karibu {app}! Code: {code}',
        'password_reset_message' => 'Reset {code} kwa {name}',
        ...$overrides,
    ];
}

test('a super admin saves the SMS gateway, sender ID and messages', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('name="sms_sender_id"', false)
        ->assertSee('name="registration_verify_phone"', false);

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), smsSettingsForm())
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasNoErrors();

    expect(SystemSettings::smsBaseUrl())->toBe('https://gateway.test/sms')
        ->and(SystemSettings::smsUsername())->toBe('jumuika')
        ->and(SystemSettings::smsPassword())->toBe('gateway-secret')
        ->and(SystemSettings::smsSenderId())->toBe('JUMUIKA')
        ->and(SystemSettings::codeLifetimeMinutes())->toBe(15)
        ->and(SystemSettings::registrationCodeMessage())->toBe('Karibu {app}! Code: {code}')
        ->and(SystemSettings::registrationRequiresPhoneCode())->toBeTrue()
        ->and(Setting::read('sms.password'))->not->toBe('gateway-secret');

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertDontSee('gateway-secret');
});

test('an empty password field keeps the saved gateway password', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)->put(route('admin.settings.update'), smsSettingsForm());
    $this->actingAs($admin)->put(route('admin.settings.update'), smsSettingsForm(['sms_password' => '', 'sms_sender_id' => 'EMCA']));

    expect(SystemSettings::smsPassword())->toBe('gateway-secret')
        ->and(SystemSettings::smsSenderId())->toBe('EMCA');
});

test('a super admin switches registration phone verification off', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), smsSettingsForm(['registration_verify_phone' => '0']))
        ->assertSessionHasNoErrors();

    expect(SystemSettings::registrationVerifiesPhone())->toBeFalse()
        ->and(SystemSettings::registrationRequiresPhoneCode())->toBeFalse();
});

test('messages must contain the code and the sender ID must fit the SMS rules', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->from(route('admin.settings.edit'))
        ->put(route('admin.settings.update'), smsSettingsForm([
            'registration_message' => 'Welcome without a code',
            'password_reset_message' => 'Reset now',
            'sms_sender_id' => 'Jumuika Pass Tz',
        ]))
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasErrors(['registration_message', 'password_reset_message', 'sms_sender_id']);

    expect(Setting::isSaved('messages.registration_code'))->toBeFalse();
});

test('a super admin sends a test SMS with the saved sender ID', function () {
    fakeSmsGateway();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.test-sms'), ['test_phone' => '0754111222'])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHasNoErrors();

    expect(lastSentSms()['to'])->toBe('255754111222')
        ->and(lastSentSms()['from'])->toBe('JUMUIKA');
});

test('a failed test SMS is reported to the admin', function () {
    fakeSmsGateway(status: 401);
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.test-sms'), ['test_phone' => '0754111222'])
        ->assertSessionHasErrors('test_phone', errorBag: 'testSms');

    Http::assertSentCount(1);
});

test('a host cannot open or change the system settings', function () {
    $host = User::factory()->create();

    $this->actingAs($host)->get(route('admin.settings.edit'))->assertForbidden();
    $this->actingAs($host)->put(route('admin.settings.update'), smsSettingsForm())->assertForbidden();
    $this->actingAs($host)->post(route('admin.settings.test-sms'), ['test_phone' => '0754111222'])->assertForbidden();

    expect(Setting::query()->count())->toBe(0);
});
