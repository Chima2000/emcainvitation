<?php

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\TopUpRequest;
use App\Models\User;
use App\PaymentMethod;
use App\SubscriptionPeriod;
use App\Support\SystemSettings;
use App\WalletEntryType;

test('a host without a subscription can only reach the subscription page', function () {
    $host = User::factory()->withoutSubscription()->create();

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.index'));

    $this->actingAs($host)
        ->get(route('wallet.index'))
        ->assertRedirect(route('subscription.index'));

    $this->actingAs($host)
        ->get(route('subscription.index'))
        ->assertOk()
        ->assertSee(__('Your account is not active yet'))
        ->assertSeeInOrder([
            SubscriptionPeriod::Week->label(), 'TZS 5,000',
            SubscriptionPeriod::Month->label(), 'TZS 15,000',
            SubscriptionPeriod::Year->label(), 'TZS 140,000',
        ]);
});

test('the subscription content spans the full available width', function () {
    $host = User::factory()->withoutSubscription()->create();

    $this->actingAs($host)
        ->get(route('subscription.index'))
        ->assertOk()
        ->assertSee('<div class="col-12">', false)
        ->assertDontSee('<div class="col-xl-8">', false);
});

test('an expired subscription locks the dashboard again', function () {
    $host = User::factory()->subscriptionExpired()->create();

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertRedirect(route('subscription.index'));
});

test('a host pays a month from the wallet and the dashboard opens', function () {
    $this->freezeSecond();
    $host = User::factory()->withoutSubscription()->create(['wallet_balance' => 20000]);

    $this->actingAs($host)
        ->post(route('subscription.store'), ['period' => SubscriptionPeriod::Month->value])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    $host->refresh();
    $subscription = $host->subscriptions()->sole();
    $entry = $host->walletTransactions()->sole();

    expect($host->wallet_balance)->toBe(5000)
        ->and($host->subscription_ends_at->equalTo(now()->addMonthNoOverflow()))->toBeTrue()
        ->and($subscription->amount)->toBe(15000)
        ->and($subscription->wallet_transaction_id)->toBe($entry->id)
        ->and($entry->type)->toBe(WalletEntryType::Subscription)
        ->and($entry->amount)->toBe(-15000);

    $this->actingAs($host)->get(route('dashboard'))->assertOk();
});

test('paying during an active subscription adds the time after the current end', function () {
    $this->freezeSecond();
    $currentEnd = now()->addDays(10);
    $host = User::factory()->create(['wallet_balance' => 5000, 'subscription_ends_at' => $currentEnd]);

    $this->actingAs($host)
        ->post(route('subscription.store'), ['period' => SubscriptionPeriod::Week->value])
        ->assertSessionHasNoErrors();

    expect($host->fresh()->subscription_ends_at->equalTo($currentEnd->copy()->addWeek()))->toBeTrue();
});

test('a wallet that cannot cover the price changes nothing', function () {
    $host = User::factory()->withoutSubscription()->create(['wallet_balance' => 4000]);

    $this->actingAs($host)
        ->from(route('subscription.index'))
        ->post(route('subscription.store'), ['period' => SubscriptionPeriod::Week->value])
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHasErrors('wallet');

    expect($host->fresh()->wallet_balance)->toBe(4000)
        ->and($host->fresh()->subscription_ends_at)->toBeNull()
        ->and(Subscription::query()->count())->toBe(0);
});

test('a period the admin switched off cannot be bought or seen', function () {
    SubscriptionPlan::forPeriod(SubscriptionPeriod::Week)->update(['is_active' => false]);
    $host = User::factory()->withoutSubscription()->create(['wallet_balance' => 50000]);

    $this->actingAs($host)
        ->get(route('subscription.index'))
        ->assertDontSee('value="week"', false)
        ->assertSee('value="month"', false);

    $this->actingAs($host)
        ->post(route('subscription.store'), ['period' => SubscriptionPeriod::Week->value])
        ->assertSessionHasErrors('period');

    expect($host->fresh()->wallet_balance)->toBe(50000);
});

test('a mobile money payment opens the account once the admin confirms it', function () {
    $this->freezeSecond();
    $host = User::factory()->withoutSubscription()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($host)
        ->post(route('subscription.payment'), [
            'period' => SubscriptionPeriod::ThreeMonths->value,
            'method' => PaymentMethod::Mpesa->value,
            'reference' => 'qk71sub3m',
        ])
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHasNoErrors();

    $payment = TopUpRequest::query()->sole();

    expect($payment->amount)->toBe(40000)
        ->and($payment->subscription_period)->toBe(SubscriptionPeriod::ThreeMonths)
        ->and($payment->reference)->toBe('QK71SUB3M');

    $this->actingAs($host)
        ->get(route('subscription.index'))
        ->assertSee('QK71SUB3M');

    $this->actingAs($admin)
        ->post(route('admin.top-ups.approve', $payment))
        ->assertSessionHasNoErrors();

    $host->refresh();

    expect($host->hasActiveSubscription())->toBeTrue()
        ->and($host->subscription_ends_at->equalTo(now()->addMonthsNoOverflow(3)))->toBeTrue()
        ->and($host->wallet_balance)->toBe(0)
        ->and($host->walletTransactions()->pluck('type')->map->value->all())->toEqualCanonicalizing([WalletEntryType::TopUp->value, WalletEntryType::Subscription->value]);

    $this->actingAs($host)->get(route('dashboard'))->assertOk();
});

test('a mobile money payment needs a method and a transaction code', function () {
    $host = User::factory()->withoutSubscription()->create();

    $this->actingAs($host)
        ->from(route('subscription.index'))
        ->post(route('subscription.payment'), ['period' => SubscriptionPeriod::Month->value])
        ->assertRedirect(route('subscription.index'))
        ->assertSessionHasErrors(['method', 'reference'], errorBag: 'subscriptionPayment');

    expect(TopUpRequest::query()->count())->toBe(0);
});

test('every host can use the dashboard when the admin switches the subscription requirement off', function () {
    SystemSettings::setSubscriptionRequired(false);
    $host = User::factory()->withoutSubscription()->create();

    $this->actingAs($host)->get(route('dashboard'))->assertOk();
});

test('a super admin sets subscription prices and switches periods off', function () {
    $admin = User::factory()->superAdmin()->create();
    $plans = collect(SubscriptionPeriod::cases())
        ->mapWithKeys(fn (SubscriptionPeriod $period): array => [$period->value => ['price' => 1000, 'is_active' => '1']])
        ->all();
    $plans['month'] = ['price' => 12500, 'is_active' => '1'];
    $plans['week'] = ['price' => 3000, 'is_active' => '0'];

    $this->actingAs($admin)
        ->put(route('admin.subscriptions.update'), ['plans' => $plans, 'required' => '1'])
        ->assertRedirect(route('admin.subscriptions.index'))
        ->assertSessionHasNoErrors();

    expect(SubscriptionPlan::forPeriod(SubscriptionPeriod::Month)->price)->toBe(12500)
        ->and(SubscriptionPlan::query()->where('period', SubscriptionPeriod::Week)->sole()->is_active)->toBeFalse()
        ->and(SystemSettings::subscriptionRequired())->toBeTrue();

    $host = User::factory()->withoutSubscription()->create();

    $this->actingAs($host)
        ->get(route('subscription.index'))
        ->assertSee('TZS 12,500')
        ->assertDontSee('value="week"', false);
});

test('a super admin turns the subscription requirement off', function () {
    $admin = User::factory()->superAdmin()->create();
    $plans = SubscriptionPlan::ordered()
        ->mapWithKeys(fn (SubscriptionPlan $plan): array => [$plan->period->value => ['price' => $plan->price, 'is_active' => '1']])
        ->all();

    $this->actingAs($admin)
        ->put(route('admin.subscriptions.update'), ['plans' => $plans, 'required' => '0'])
        ->assertSessionHasNoErrors();

    expect(SystemSettings::subscriptionRequired())->toBeFalse();
});

test('a host cannot change subscription prices', function () {
    $host = User::factory()->create();

    $this->actingAs($host)->get(route('admin.subscriptions.index'))->assertForbidden();
    $this->actingAs($host)
        ->put(route('admin.subscriptions.update'), ['plans' => ['month' => ['price' => 1]]])
        ->assertForbidden();

    expect(SubscriptionPlan::forPeriod(SubscriptionPeriod::Month)->price)->toBe(15000);
});
