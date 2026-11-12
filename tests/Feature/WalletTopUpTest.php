<?php

use App\Models\TopUpRequest;
use App\Models\User;
use App\PaymentMethod;
use App\TopUpStatus;
use App\WalletEntryType;

test('a host sends a top-up and the balance waits for confirmation', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->post(route('wallet.top-ups.store'), [
            'amount' => 50000,
            'method' => PaymentMethod::Mpesa->value,
            'reference' => ' qk71 abc2xy ',
            'payer_phone' => '0754111222',
        ])
        ->assertRedirect(route('wallet.index'))
        ->assertSessionHasNoErrors();

    $topUp = TopUpRequest::query()->sole();

    expect($topUp->user_id)->toBe($host->id)
        ->and($topUp->reference)->toBe('QK71ABC2XY')
        ->and($topUp->status)->toBe(TopUpStatus::Pending)
        ->and($host->fresh()->wallet_balance)->toBe(0);
});

test('a payment reference can only be claimed once per method', function () {
    $host = User::factory()->create();
    TopUpRequest::factory()->create(['method' => PaymentMethod::Mpesa, 'reference' => 'QK71ABC2XY']);

    $this->actingAs($host)
        ->from(route('wallet.index'))
        ->post(route('wallet.top-ups.store'), [
            'amount' => 50000,
            'method' => PaymentMethod::Mpesa->value,
            'reference' => 'qk71abc2xy',
        ])
        ->assertRedirect(route('wallet.index'))
        ->assertSessionHasErrors('reference', errorBag: 'topUp');

    expect(TopUpRequest::query()->count())->toBe(1);
});

test('a top-up below the minimum reopens the popup with the error', function () {
    $host = User::factory()->create();

    $this->actingAs($host)
        ->from(route('wallet.index'))
        ->followingRedirects()
        ->post(route('wallet.top-ups.store'), [
            'amount' => 10,
            'method' => PaymentMethod::AirtelMoney->value,
            'reference' => 'AB12CD34',
        ])
        ->assertOk()
        ->assertSee('id="topUpModal"', false)
        ->assertSee('data-emca-open="1"', false);

    expect(TopUpRequest::query()->count())->toBe(0);
});

test('a super admin confirms a top-up and the host wallet is credited once', function () {
    $admin = User::factory()->superAdmin()->create();
    $topUp = TopUpRequest::factory()->create(['amount' => 75000]);

    $this->actingAs($admin)
        ->post(route('admin.top-ups.approve', $topUp))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $host = $topUp->user->fresh();
    $entry = $host->walletTransactions()->sole();

    expect($host->wallet_balance)->toBe(75000)
        ->and($entry->type)->toBe(WalletEntryType::TopUp)
        ->and($entry->amount)->toBe(75000)
        ->and($entry->balance_after)->toBe(75000)
        ->and($entry->recorded_by)->toBe($admin->id)
        ->and($topUp->fresh()->status)->toBe(TopUpStatus::Approved);

    $this->actingAs($admin)
        ->post(route('admin.top-ups.approve', $topUp))
        ->assertSessionHasErrors('top_up');

    expect($host->fresh()->wallet_balance)->toBe(75000);
});

test('a super admin declines a top-up with a note and the wallet stays the same', function () {
    $admin = User::factory()->superAdmin()->create();
    $topUp = TopUpRequest::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.top-ups.reject', $topUp), ['review_note' => ''])
        ->assertSessionHasErrors('review_note', errorBag: 'review');

    $this->actingAs($admin)
        ->post(route('admin.top-ups.reject', $topUp), ['review_note' => 'Reference not found'])
        ->assertSessionHasNoErrors();

    expect($topUp->fresh()->status)->toBe(TopUpStatus::Rejected)
        ->and($topUp->fresh()->review_note)->toBe('Reference not found')
        ->and($topUp->user->fresh()->wallet_balance)->toBe(0);
});

test('a host cannot review top-ups', function () {
    $host = User::factory()->create();
    $topUp = TopUpRequest::factory()->create();

    $this->actingAs($host)
        ->post(route('admin.top-ups.approve', $topUp))
        ->assertForbidden();

    expect($topUp->fresh()->status)->toBe(TopUpStatus::Pending);
});

test('a host only sees their own statement', function () {
    $host = User::factory()->create(['wallet_balance' => 5000]);
    $otherHost = User::factory()->create();
    $host->walletTransactions()->create([
        'type' => WalletEntryType::TopUp,
        'amount' => 5000,
        'balance_after' => 5000,
        'description' => 'My own top-up',
    ]);
    $otherHost->walletTransactions()->create([
        'type' => WalletEntryType::TopUp,
        'amount' => 9000,
        'balance_after' => 9000,
        'description' => 'Somebody else top-up',
    ]);

    $this->actingAs($host)
        ->get(route('wallet.index'))
        ->assertOk()
        ->assertSee('TZS 5,000')
        ->assertSee('My own top-up')
        ->assertDontSee('Somebody else top-up');
});
