<?php

use App\MessagePurpose;
use App\Models\Event;
use App\Models\Guest;
use App\Models\OutboundMessage;
use App\Models\Package;
use App\Models\User;
use App\WalletEntryType;

test('pay as you go invitations charge the card and channel rate per guest', function () {
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->count(3)->create();

    $this->actingAs($host)
        ->post(route('events.invitations.send', $event), ['channel' => 'whatsapp'])
        ->assertRedirect(route('events.show', $event).'#messages')
        ->assertSessionHas('status');

    $entry = $host->walletTransactions()->sole();

    expect(OutboundMessage::query()->count())->toBe(3)
        ->and(OutboundMessage::query()->pluck('cost')->unique()->all())->toBe([1300])
        ->and($host->fresh()->wallet_balance)->toBe(10000 - 3900)
        ->and($entry->type)->toBe(WalletEntryType::MessageCharge)
        ->and($entry->amount)->toBe(-3900);
});

test('sms invitations use the sms rate', function () {
    $host = User::factory()->create(['wallet_balance' => 10000]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create();

    $this->actingAs($host)->post(route('events.invitations.send', $event), ['channel' => 'sms']);

    expect(OutboundMessage::query()->sole()->cost)->toBe(1090)
        ->and($host->fresh()->wallet_balance)->toBe(8910);
});

test('sending pauses when the wallet cannot cover the next guest', function () {
    $host = User::factory()->create(['wallet_balance' => 2700]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->count(3)->create();

    $this->actingAs($host)
        ->post(route('events.invitations.send', $event), ['channel' => 'whatsapp'])
        ->assertSessionHas('wallet_paused');

    expect(OutboundMessage::query()->count())->toBe(2)
        ->and($host->fresh()->wallet_balance)->toBe(100)
        ->and($event->guests()->whereNull('sent_at')->count())->toBe(1);
});

test('nothing is prepared or charged when the wallet is empty', function () {
    $host = User::factory()->create(['wallet_balance' => 0]);
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->count(2)->create();

    $this->actingAs($host)
        ->post(route('events.invitations.send', $event), ['channel' => 'whatsapp'])
        ->assertSessionHas('error')
        ->assertSessionHas('wallet_paused');

    expect(OutboundMessage::query()->count())->toBe(0)
        ->and($host->walletTransactions()->count())->toBe(0);
});

test('a package covers invitations up to its guest limit before the wallet pays', function () {
    $host = User::factory()->create(['wallet_balance' => 300000]);
    $event = Event::factory()->for($host)->create();
    $package = Package::factory()->create(['price' => 120000, 'guest_limit' => 2]);
    Guest::factory()->for($event)->count(3)->create();

    $this->actingAs($host)->post(route('events.package.store', $event), ['package_id' => $package->id]);
    $this->actingAs($host)->post(route('events.invitations.send', $event), ['channel' => 'whatsapp']);

    expect(OutboundMessage::query()->where('covered_by_package', true)->count())->toBe(2)
        ->and(OutboundMessage::query()->where('covered_by_package', false)->sole()->cost)->toBe(1300)
        ->and($host->fresh()->wallet_balance)->toBe(300000 - 120000 - 1300)
        ->and($event->fresh()->packageAllowanceLeft(MessagePurpose::Invitation))->toBe(0);
});

test('a package without thank-yous charges thank-you messages to the wallet', function () {
    $host = User::factory()->create(['wallet_balance' => 200000]);
    $event = Event::factory()->for($host)->create();
    $package = Package::factory()->create(['price' => 120000, 'includes_thanks' => false]);
    Guest::factory()->for($event)->create(['checked_in_at' => now()]);

    $this->actingAs($host)->post(route('events.package.store', $event), ['package_id' => $package->id]);
    $this->actingAs($host)->post(route('events.thanks.send', $event), ['channel' => 'whatsapp']);

    expect(OutboundMessage::query()->sole()->cost)->toBe(100)
        ->and($host->fresh()->wallet_balance)->toBe(200000 - 120000 - 100);
});

test('the event page shows the price per message and the wallet balance', function () {
    $host = User::factory()->create(['wallet_balance' => 45000]);
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('id="billing"', false)
        ->assertSee('TZS 45,000')
        ->assertSee('TZS 1,300');
});

test('the event page shows only the 15 most recent messages', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    foreach (range(0, 15) as $index) {
        OutboundMessage::factory()->for($event)->create([
            'body' => "Message {$index}",
            'created_at' => now()->addSeconds($index),
        ]);
    }

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertDontSee('Message 0')
        ->assertSee('Message 15');
});
