<?php

use App\Models\Event;
use App\Models\Package;
use App\Models\User;
use App\WalletEntryType;

test('a host buys a package for an event from the wallet', function () {
    $host = User::factory()->create(['wallet_balance' => 300000]);
    $event = Event::factory()->for($host)->create();
    $package = Package::factory()->create(['price' => 260000, 'guest_limit' => 200, 'reminder_rounds' => 1]);

    $this->actingAs($host)
        ->post(route('events.package.store', $event), ['package_id' => $package->id])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasNoErrors();

    $event->refresh();
    $entry = $host->walletTransactions()->sole();

    expect($host->fresh()->wallet_balance)->toBe(40000)
        ->and($event->package_id)->toBe($package->id)
        ->and($event->package_guest_limit)->toBe(200)
        ->and($event->package_reminder_rounds)->toBe(1)
        ->and($event->package_price_paid)->toBe(260000)
        ->and($entry->type)->toBe(WalletEntryType::PackagePurchase)
        ->and($entry->amount)->toBe(-260000)
        ->and($entry->event_id)->toBe($event->id);
});

test('upgrading a package only charges the difference', function () {
    $host = User::factory()->create(['wallet_balance' => 400000]);
    $event = Event::factory()->for($host)->create();
    $starter = Package::factory()->create(['price' => 120000]);
    $plus = Package::factory()->create(['price' => 390000, 'guest_limit' => 300]);

    $this->actingAs($host)->post(route('events.package.store', $event), ['package_id' => $starter->id]);
    $this->actingAs($host)->post(route('events.package.store', $event), ['package_id' => $plus->id])
        ->assertSessionHasNoErrors();

    expect($host->fresh()->wallet_balance)->toBe(10000)
        ->and($event->fresh()->package_id)->toBe($plus->id)
        ->and($event->fresh()->package_price_paid)->toBe(390000);
});

test('a host cannot switch to a cheaper or equal package', function () {
    $host = User::factory()->create(['wallet_balance' => 400000]);
    $event = Event::factory()->for($host)->create();
    $standard = Package::factory()->create(['price' => 260000]);
    $starter = Package::factory()->create(['price' => 120000]);

    $this->actingAs($host)->post(route('events.package.store', $event), ['package_id' => $standard->id]);
    $this->actingAs($host)->post(route('events.package.store', $event), ['package_id' => $starter->id])
        ->assertSessionHasErrors('package_id');

    expect($host->fresh()->wallet_balance)->toBe(140000)
        ->and($event->fresh()->package_id)->toBe($standard->id);
});

test('a package is refused when the wallet cannot cover it', function () {
    $host = User::factory()->create(['wallet_balance' => 1000]);
    $event = Event::factory()->for($host)->create();
    $package = Package::factory()->create(['price' => 120000]);

    $this->actingAs($host)
        ->post(route('events.package.store', $event), ['package_id' => $package->id])
        ->assertSessionHasErrors('wallet');

    expect($host->fresh()->wallet_balance)->toBe(1000)
        ->and($event->fresh()->package_id)->toBeNull()
        ->and($host->walletTransactions()->count())->toBe(0);
});

test('a hidden package cannot be bought', function () {
    $host = User::factory()->create(['wallet_balance' => 500000]);
    $event = Event::factory()->for($host)->create();
    $package = Package::factory()->inactive()->create();

    $this->actingAs($host)
        ->post(route('events.package.store', $event), ['package_id' => $package->id])
        ->assertSessionHasErrors('package_id');

    expect($host->fresh()->wallet_balance)->toBe(500000);
});

test('a host cannot buy a package for somebody else event', function () {
    $host = User::factory()->create(['wallet_balance' => 500000]);
    $otherEvent = Event::factory()->create();
    $package = Package::factory()->create();

    $this->actingAs($host)
        ->post(route('events.package.store', $otherEvent), ['package_id' => $package->id])
        ->assertNotFound();

    expect($host->fresh()->wallet_balance)->toBe(500000);
});

test('the packages page lists active packages and the host upcoming events', function () {
    $host = User::factory()->create();
    Event::factory()->for($host)->create(['title' => 'Harusi ya Neema', 'event_date' => now()->addMonth()]);
    Package::factory()->create(['name' => 'Wedding Standard']);
    Package::factory()->inactive()->create(['name' => 'Retired Plan']);

    $this->actingAs($host)
        ->get(route('packages.index'))
        ->assertOk()
        ->assertSee('Wedding Standard')
        ->assertDontSee('Retired Plan')
        ->assertSee('Harusi ya Neema')
        ->assertSee('id="buyPackageModal"', false);
});
