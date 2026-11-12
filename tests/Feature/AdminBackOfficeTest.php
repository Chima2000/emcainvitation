<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\InvitationTemplate;
use App\Models\Package;
use App\Models\ServiceRate;
use App\Models\TopUpRequest;
use App\Models\User;
use App\ServiceItem;
use App\WalletEntryType;

dataset('admin pages', [
    'top-ups' => ['admin.top-ups.index'],
    'transactions' => ['admin.transactions.index'],
    'events' => ['admin.events.index'],
    'messages' => ['admin.messages.index'],
    'packages' => ['admin.packages.index'],
    'pricing' => ['admin.pricing.edit'],
    'templates' => ['admin.templates.index'],
    'event categories' => ['admin.event-categories.index'],
    'subscriptions' => ['admin.subscriptions.index'],
    'settings' => ['admin.settings.edit'],
    'hosts' => ['admin.users.index'],
    'search' => ['admin.search'],
    'demo requests' => ['demos.index'],
]);

test('a super admin opens every back-office page in the admin layout', function (string $routeName) {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route($routeName))
        ->assertOk()
        ->assertSee('class="app-menubar"', false)
        ->assertDontSee('app-menubar-tabs', false)
        ->assertSee('id="emcaConfirmModal"', false);
})->with('admin pages');

test('the admin sidebar lists every back-office section', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder([__('Administration'), __('Customers'), __('Events'), __('Finance'), __('Catalog'), __('Public website')])
        ->assertSee(route('admin.top-ups.index'), false)
        ->assertSee(route('admin.transactions.index', ['type' => 'package_purchase']), false)
        ->assertSee(route('admin.pricing.edit'), false)
        ->assertSee(route('admin.templates.index'), false)
        ->assertSee(route('events.upcoming'), false);
});

test('the admin dashboard shows collections and pending top-ups in the main grid', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['name' => 'Zawadi Mushi']);
    TopUpRequest::factory()->for($host)->create(['reference' => 'ZM77TOPUP']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('app-sidebar-panel', false)
        ->assertSee(__('Collections this month'))
        ->assertSee(__('Top packages'))
        ->assertSee(__('Waiting for confirmation'))
        ->assertSee('ZM77TOPUP');
});

test('back-office lists render real records', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['name' => 'Rehema Juma']);
    $package = Package::factory()->create(['name' => 'Wedding Standard']);
    $event = Event::factory()->for($host)->create(['title' => 'Harusi ya Rehema', 'package_id' => $package->id]);
    $guest = Guest::factory()->for($event)->create(['name' => 'Baraka Mollel']);
    $event->messages()->create([
        'guest_id' => $guest->id,
        'channel' => 'whatsapp',
        'purpose' => 'invitation',
        'body' => 'Karibu',
        'status' => 'sent',
        'cost' => 1300,
        'covered_by_package' => false,
        'sent_at' => now(),
    ]);
    TopUpRequest::factory()->for($host)->create(['reference' => 'RJ55TOPUP']);
    $host->walletTransactions()->create([
        'event_id' => $event->id,
        'type' => WalletEntryType::MessageCharge,
        'amount' => -1300,
        'balance_after' => 0,
        'description' => '1 invitation message by WhatsApp',
    ]);
    InvitationTemplate::factory()->create(['name' => 'Kanga Gold']);

    $this->actingAs($admin);

    $this->get(route('admin.top-ups.index'))->assertOk()->assertSee('RJ55TOPUP')->assertSee('id="rejectTopUpModal"', false);
    $this->get(route('admin.transactions.index'))->assertOk()->assertSee('1 invitation message by WhatsApp');
    $this->get(route('admin.messages.index'))->assertOk()->assertSee('Baraka Mollel');
    $this->get(route('admin.events.index'))->assertOk()->assertSee('Harusi ya Rehema')->assertSee('Wedding Standard');
    $this->get(route('admin.events.show', $event))->assertOk()->assertSee('Baraka Mollel');
    $this->get(route('admin.packages.index'))->assertOk()->assertSee('Wedding Standard');
    $this->get(route('admin.templates.index'))->assertOk()->assertSee('Kanga Gold');
    $this->get(route('admin.users.index', ['q' => 'Rehema']))->assertOk()->assertSee('Rehema Juma');
});

test('a host cannot open back-office pages', function (string $routeName) {
    $host = User::factory()->create();

    $response = $this->actingAs($host)->get(route($routeName));

    expect($response->status())->toBeIn([403, 404]);
})->with('admin pages');

test('a super admin opens a host profile with wallet and events', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['name' => 'Neema Ally', 'wallet_balance' => 12500]);
    Event::factory()->for($host)->create(['title' => 'Harusi ya Neema']);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $host))
        ->assertOk()
        ->assertSee('Neema Ally')
        ->assertSee('TZS 12,500')
        ->assertSee('Harusi ya Neema')
        ->assertSee('id="walletAdjustmentModal"', false);
});

test('a super admin opens an event detail page', function () {
    $admin = User::factory()->superAdmin()->create();
    $event = Event::factory()->create(['title' => 'Send-off ya Amina']);

    $this->actingAs($admin)
        ->get(route('admin.events.show', $event))
        ->assertOk()
        ->assertSee('Send-off ya Amina');
});

test('a super admin credits and debits a host wallet with a reason', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['wallet_balance' => 1000]);

    $this->actingAs($admin)
        ->post(route('admin.users.wallet.store', $host), ['direction' => 'credit', 'amount' => 4000, 'reason' => 'Refund for duplicate SMS'])
        ->assertRedirect(route('admin.users.show', $host));

    $this->actingAs($admin)
        ->post(route('admin.users.wallet.store', $host), ['direction' => 'debit', 'amount' => 2000, 'reason' => 'Correction']);

    $entries = $host->walletTransactions()->orderBy('id')->get();

    expect($host->fresh()->wallet_balance)->toBe(3000)
        ->and($entries->pluck('amount')->all())->toBe([4000, -2000])
        ->and($entries->pluck('type')->unique()->all())->toBe([WalletEntryType::Adjustment])
        ->and($entries->first()->recorded_by)->toBe($admin->id);
});

test('a wallet debit cannot take the balance below zero', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['wallet_balance' => 500]);

    $this->actingAs($admin)
        ->post(route('admin.users.wallet.store', $host), ['direction' => 'debit', 'amount' => 900, 'reason' => 'Correction'])
        ->assertSessionHasErrors('wallet');

    expect($host->fresh()->wallet_balance)->toBe(500);
});

test('a wallet adjustment needs a reason and stays in the popup', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create();

    $this->actingAs($admin)
        ->from(route('admin.users.show', $host))
        ->post(route('admin.users.wallet.store', $host), ['direction' => 'credit', 'amount' => 1000, 'reason' => ''])
        ->assertRedirect(route('admin.users.show', $host))
        ->assertSessionHasErrors('reason', errorBag: 'adjustment');

    $this->actingAs($admin)
        ->from(route('admin.users.show', $host))
        ->followingRedirects()
        ->post(route('admin.users.wallet.store', $host), ['direction' => 'credit', 'amount' => 1000, 'reason' => ''])
        ->assertOk()
        ->assertSee('data-emca-open="1"', false);

    expect($host->walletTransactions()->count())->toBe(0);
});

test('a super admin creates, edits and retires packages', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.packages.store'), [
            'name' => 'Wedding Plus',
            'guest_limit' => 300,
            'price' => 390000,
            'channel' => 'whatsapp',
            'reminder_rounds' => 2,
            'includes_thanks' => '1',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.packages.index'))
        ->assertSessionHasNoErrors();

    $package = Package::query()->where('name', 'Wedding Plus')->sole();

    expect($package->includes_thanks)->toBeTrue()
        ->and($package->is_featured)->toBeFalse();

    $this->actingAs($admin)
        ->put(route('admin.packages.update', $package), [
            'name' => 'Wedding Plus',
            'guest_limit' => 300,
            'price' => 380000,
            'channel' => 'whatsapp',
            'reminder_rounds' => 2,
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect($package->fresh()->price)->toBe(380000)
        ->and($package->fresh()->includes_thanks)->toBeFalse();

    Event::factory()->create(['package_id' => $package->id]);

    $this->actingAs($admin)->delete(route('admin.packages.destroy', $package));

    expect($package->fresh())->not->toBeNull()
        ->and($package->fresh()->is_active)->toBeFalse();

    $unused = Package::factory()->create();

    $this->actingAs($admin)->delete(route('admin.packages.destroy', $unused));

    $this->assertModelMissing($unused);
});

test('an invalid package keeps the popup open with the errors', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->from(route('admin.packages.index'))
        ->post(route('admin.packages.store'), ['name' => '', 'channel' => 'whatsapp'])
        ->assertRedirect(route('admin.packages.index'))
        ->assertSessionHasErrors(['name', 'guest_limit', 'price'], errorBag: 'package');

    $this->actingAs($admin)
        ->from(route('admin.packages.index'))
        ->followingRedirects()
        ->post(route('admin.packages.store'), ['name' => '', 'channel' => 'whatsapp'])
        ->assertOk()
        ->assertSee('data-emca-open="1"', false);
});

test('a super admin changes message prices and new messages use them', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->put(route('admin.pricing.update'), ['rates' => [
            'card' => 800,
            'whatsapp' => 250,
            'sms' => 80,
            'reminder' => 150,
            'thank_you' => 100,
        ]])
        ->assertRedirect(route('admin.pricing.edit'))
        ->assertSessionHasNoErrors();

    expect(ServiceRate::amountFor(ServiceItem::Card))->toBe(800)
        ->and(ServiceRate::amountFor(ServiceItem::Whatsapp))->toBe(250);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('TZS 800');
});

test('a super admin manages card templates', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.templates.store'), [
            'name' => 'Kanga Gold',
            'category' => 'wedding',
            'price' => 0,
            'paper' => '#FAF7F7',
            'ink' => '#16171A',
            'accent' => '#940000',
            'motif' => 'Kanga border',
            'description' => 'Warm gold border for church weddings.',
        ])
        ->assertSessionHasNoErrors();

    $template = InvitationTemplate::query()->where('name', 'Kanga Gold')->sole();

    $this->actingAs($admin)
        ->put(route('admin.templates.update', $template), [
            'name' => 'Kanga Gold',
            'category' => 'wedding',
            'price' => 5000,
            'paper' => 'not-a-colour',
            'ink' => '#16171A',
            'accent' => '#940000',
            'motif' => 'Kanga border',
            'description' => 'Warm gold border.',
        ])
        ->assertSessionHasErrors('paper', errorBag: 'template');

    $this->actingAs($admin)->delete(route('admin.templates.destroy', $template));

    $this->assertModelMissing($template);
});

test('admin search finds hosts, events and payment references', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['name' => 'Zawadi Mushi']);
    Event::factory()->for($host)->create(['title' => 'Zawadi Birthday']);
    TopUpRequest::factory()->for($host)->create(['reference' => 'ZAWADI123']);

    $this->actingAs($admin)
        ->get(route('admin.search', ['q' => 'zawadi']))
        ->assertOk()
        ->assertSee('Zawadi Mushi')
        ->assertSee('Zawadi Birthday')
        ->assertSee('ZAWADI123');
});

test('the admin dashboard shows money figures from the ledger', function () {
    $admin = User::factory()->superAdmin()->create();
    $host = User::factory()->create(['wallet_balance' => 20000]);
    TopUpRequest::factory()->for($host)->create(['amount' => 15000]);
    $host->walletTransactions()->create([
        'type' => WalletEntryType::PackagePurchase,
        'amount' => -120000,
        'balance_after' => 20000,
        'description' => 'Family Starter',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewHas('stats', fn (array $stats): bool => $stats['revenue_month'] === 120000
            && $stats['wallet_float'] === 20000
            && $stats['pending_top_ups'] === 1
            && $stats['pending_top_up_amount'] === 15000);
});
