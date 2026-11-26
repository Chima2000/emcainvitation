<?php

use App\Mail\DemoTrialInvitation;
use App\MessageChannel;
use App\MessagePurpose;
use App\MessageStatus;
use App\Models\DemoRequest;
use App\Models\OutboundMessage;
use App\Models\Subscription;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('a super admin grants a trial and the client receives the link by sms and email', function () {
    fakeSmsGateway();
    Mail::fake();

    $admin = User::factory()->superAdmin()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->post(route('demos.trial', $demo), ['days' => 14])
        ->assertRedirect(route('demos.index'))
        ->assertSessionHas('status', __('A :days-day trial is active. The link was sent by SMS and email.', ['days' => 14]));

    $user = User::query()->where('phone', '+255712345678')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasActiveSubscription())->toBeTrue()
        ->and($user->subscription_ends_at->toDateString())->toBe(now()->addDays(14)->toDateString())
        ->and($demo->refresh()->user_id)->toBe($user->id)
        ->and($demo->must_set_password)->toBeTrue()
        ->and($demo->trial_days)->toBe(14)
        ->and($user->role)->toBe(UserRole::Lead);

    $sentUrl = null;

    Mail::assertSent(DemoTrialInvitation::class, function (DemoTrialInvitation $mail) use (&$sentUrl): bool {
        $sentUrl = $mail->url;

        return $mail->hasTo('asha@example.com') && $mail->days === 14;
    });

    expect(lastSentSms()['to'])->toBe('255712345678')
        ->and(lastSentSms()['text'])->toContain($sentUrl);

    $logged = OutboundMessage::query()->where('purpose', MessagePurpose::Trial)->get();

    expect($logged)->toHaveCount(2)
        ->and($logged->firstWhere('channel', MessageChannel::Sms)?->status)->toBe(MessageStatus::Delivered)
        ->and($logged->firstWhere('channel', MessageChannel::Sms)?->recipient_name)->toBe('Asha Mushi')
        ->and($logged->firstWhere('channel', MessageChannel::Email)?->status)->toBe(MessageStatus::Delivered)
        ->and($logged->firstWhere('channel', MessageChannel::Email)?->recipient_address)->toBe('asha@example.com')
        ->and($logged->firstWhere('channel', MessageChannel::Email)?->event_id)->toBeNull();

    $this->get($sentUrl)
        ->assertOk()
        ->assertSee(__('Choose a password'));

    $this->post($sentUrl, [
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors([
        'password' => 'Sehemu ya nenosiri iwe na herufi angalau 9.',
    ]);

    $this->post($sentUrl, [
        'password' => 'Secret@12',
        'password_confirmation' => 'Secret@12',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($demo->refresh()->must_set_password)->toBeFalse()
        ->and(Hash::check('Secret@12', $user->refresh()->password))->toBeTrue();
});

test('granting a trial turns an unpaid account into a lead without replacing the password', function () {
    fakeSmsGateway();
    Mail::fake();
    $this->travelTo('2026-11-13 09:00:00');

    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '+255712345678',
        'password' => 'Secret@12',
        'subscription_ends_at' => now()->addDays(10),
    ]);
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->post(route('demos.trial', $demo), ['days' => 5])
        ->assertRedirect(route('demos.index'));

    expect($host->refresh()->subscription_ends_at->equalTo(now()->addDays(15)))->toBeTrue()
        ->and(Hash::check('Secret@12', $host->password))->toBeTrue()
        ->and($demo->refresh()->must_set_password)->toBeFalse()
        ->and(User::query()->where('email', 'asha@example.com')->count())->toBe(1)
        ->and($host->role)->toBe(UserRole::Lead);
});

test('granting a trial leaves a paying host as a host', function () {
    fakeSmsGateway();
    Mail::fake();

    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '+255712345678',
    ]);
    Subscription::factory()->create(['user_id' => $host->id]);
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->post(route('demos.trial', $demo), ['days' => 5])
        ->assertRedirect(route('demos.index'));

    expect($host->refresh()->role)->toBe(UserRole::Host);
});

test('a log mailer is not reported as a delivered email', function () {
    config(['mail.default' => 'log']);
    Mail::fake();

    $admin = User::factory()->superAdmin()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->post(route('demos.trial', $demo), ['days' => 7])
        ->assertRedirect(route('demos.index'))
        ->assertSessionHas('error', __('A :days-day trial is active, but nothing was delivered. The SMS gateway is not set up, and email is only saved in the application log. Copy the trial link from the list.', ['days' => 7]));

    expect(User::query()->where('phone', '+255712345678')->exists())->toBeTrue()
        ->and(OutboundMessage::query()->where('purpose', MessagePurpose::Trial)->where('status', MessageStatus::Failed)->count())->toBe(2);
});

test('a trial is still granted when sms is not configured', function () {
    Mail::fake();

    $admin = User::factory()->superAdmin()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->post(route('demos.trial', $demo), ['days' => 7])
        ->assertRedirect(route('demos.index'))
        ->assertSessionHas('error', __('A :days-day trial is active. The email was sent, but the SMS was not.', ['days' => 7]));

    Mail::assertSent(DemoTrialInvitation::class);
    expect(User::query()->where('phone', '+255712345678')->exists())->toBeTrue()
        ->and(OutboundMessage::query()->where('channel', MessageChannel::Email)->sole()->status)->toBe(MessageStatus::Delivered)
        ->and(OutboundMessage::query()->where('channel', MessageChannel::Sms)->sole()->status)->toBe(MessageStatus::Failed);
});

test('a host cannot grant a demo trial', function () {
    $host = User::factory()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($host)
        ->post(route('demos.trial', $demo), ['days' => 7])
        ->assertNotFound();
});

test('trial days outside 1 to 365 are rejected', function () {
    $admin = User::factory()->superAdmin()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->from(route('demos.index'))
        ->post(route('demos.trial', $demo), ['days' => 0])
        ->assertRedirect(route('demos.index'))
        ->assertSessionHasErrors([
            'days' => __('Choose between 1 and 365 days.'),
        ], errorBag: 'trial-'.$demo->id);

    expect(User::query()->where('phone', '+255712345678')->exists())->toBeFalse();
});

test('a trial is refused when the phone and email belong to different accounts', function () {
    fakeSmsGateway();
    Mail::fake();

    $admin = User::factory()->superAdmin()->create();
    User::factory()->create(['phone' => '+255712345678', 'email' => 'phone-owner@example.com']);
    User::factory()->create(['phone' => '+255713333333', 'email' => 'asha@example.com']);
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->from(route('demos.index'))
        ->post(route('demos.trial', $demo), ['days' => 7])
        ->assertRedirect(route('demos.index'))
        ->assertSessionHasErrors([
            'days' => __('This phone and email belong to different accounts.'),
        ], errorBag: 'trial-'.$demo->id);

    Mail::assertNothingSent();
    expect($demo->refresh()->trial_granted_at)->toBeNull();
});

test('a super admin can cancel an active trial and the start link stops working', function () {
    fakeSmsGateway();
    Mail::fake();

    $admin = User::factory()->superAdmin()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)->post(route('demos.trial', $demo), ['days' => 14]);

    $sentUrl = null;
    Mail::assertSent(DemoTrialInvitation::class, function (DemoTrialInvitation $mail) use (&$sentUrl): bool {
        $sentUrl = $mail->url;

        return true;
    });

    $this->post(route('demos.trial.cancel', $demo))
        ->assertRedirect(route('demos.index'))
        ->assertSessionHas('status', __('The trial for :name is cancelled.', ['name' => 'Asha Mushi']));

    $user = User::query()->where('phone', '+255712345678')->first();

    expect($demo->refresh()->trial_cancelled_at)->not->toBeNull()
        ->and($demo->trialIsActive())->toBeFalse()
        ->and($user->hasActiveSubscription())->toBeFalse();

    $this->get($sentUrl)->assertNotFound();
});

test('cancelling a trial leaves paid time that was already on the account', function () {
    fakeSmsGateway();
    Mail::fake();
    $this->travelTo('2026-11-13 09:00:00');

    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create([
        'email' => 'asha@example.com',
        'phone' => '+255712345678',
        'subscription_ends_at' => now()->addDays(10),
    ]);
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->post(route('demos.trial', $demo), ['days' => 5])
        ->assertRedirect(route('demos.index'));

    $this->post(route('demos.trial.cancel', $demo))
        ->assertRedirect(route('demos.index'))
        ->assertSessionHas('status', __('The trial for :name is cancelled. Paid access continues until :date.', [
            'name' => 'Asha Mushi',
            'date' => now()->addDays(10)->timezone(config('app.timezone'))->translatedFormat('j M Y, g:i A'),
        ]));

    expect($host->refresh()->subscription_ends_at->equalTo(now()->addDays(10)))->toBeTrue()
        ->and($demo->refresh()->trialIsActive())->toBeFalse();
});

test('an admin opens WhatsApp with the trial link and the message is logged', function () {
    fakeSmsGateway();
    Mail::fake();

    $admin = User::factory()->superAdmin()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->post(route('demos.trial', $demo), ['days' => 7])
        ->assertRedirect(route('demos.index'));

    $response = $this->post(route('demos.trial.whatsapp', $demo));
    $location = (string) $response->headers->get('Location');

    $response->assertRedirect();
    expect($location)->toStartWith('https://wa.me/255712345678?text=')
        ->and($location)->toContain(rawurlencode('/trial/'.$demo->id));

    $logged = OutboundMessage::query()->where('channel', MessageChannel::Whatsapp)->sole();

    expect($logged->purpose)->toBe(MessagePurpose::Trial)
        ->and($logged->status)->toBe(MessageStatus::Sent)
        ->and($logged->recipient_name)->toBe('Asha Mushi')
        ->and($logged->recipient_address)->toBe('+255712345678');
});

test('a host cannot send a trial on WhatsApp', function () {
    $host = User::factory()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
        'trial_ends_at' => now()->addWeek(),
        'trial_days' => 7,
    ]);

    $this->actingAs($host)
        ->post(route('demos.trial.whatsapp', $demo))
        ->assertNotFound();

    expect(OutboundMessage::query()->count())->toBe(0);
});

test('whatsapp is refused when the trial is not active', function () {
    $admin = User::factory()->superAdmin()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($admin)
        ->from(route('demos.index'))
        ->post(route('demos.trial.whatsapp', $demo))
        ->assertRedirect(route('demos.index'))
        ->assertSessionHasErrors([
            'days' => __('There is no active trial to send on WhatsApp.'),
        ], errorBag: 'trial-'.$demo->id);

    expect(OutboundMessage::query()->count())->toBe(0);
});

test('a super admin removes a demo request and keeps the trial account', function () {
    $admin = User::factory()->superAdmin()->create();
    $lead = User::factory()->create(['role' => UserRole::Lead, 'name' => 'Asha Mushi']);
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);
    $demo->forceFill(['user_id' => $lead->id])->save();
    $other = DemoRequest::factory()->create([
        'name' => 'Neema Ally',
        'email' => 'neema@example.com',
        'phone' => '0711111111',
    ]);

    $this->actingAs($admin)
        ->delete(route('demos.destroy', $demo))
        ->assertRedirect(route('demos.index'))
        ->assertSessionHas('status', __('The demo request from :name was removed.', ['name' => 'Asha Mushi']));

    expect(DemoRequest::query()->whereKey($demo->id)->exists())->toBeFalse()
        ->and(DemoRequest::query()->whereKey($other->id)->exists())->toBeTrue()
        ->and(User::query()->whereKey($lead->id)->exists())->toBeTrue();
});

test('a host cannot remove a demo request', function () {
    $host = User::factory()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->actingAs($host)
        ->delete(route('demos.destroy', $demo))
        ->assertNotFound();

    expect(DemoRequest::query()->whereKey($demo->id)->exists())->toBeTrue();
});

test('a guest is sent to sign in before removing a demo request', function () {
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
    ]);

    $this->delete(route('demos.destroy', $demo))
        ->assertRedirect(route('login'));

    expect(DemoRequest::query()->whereKey($demo->id)->exists())->toBeTrue();
});

test('a host cannot cancel a demo trial', function () {
    $host = User::factory()->create();
    $demo = DemoRequest::factory()->create([
        'name' => 'Asha Mushi',
        'email' => 'asha@example.com',
        'phone' => '0712345678',
        'trial_ends_at' => now()->addWeek(),
    ]);

    $this->actingAs($host)
        ->post(route('demos.trial.cancel', $demo))
        ->assertNotFound();

    expect($demo->refresh()->trial_cancelled_at)->toBeNull();
});
