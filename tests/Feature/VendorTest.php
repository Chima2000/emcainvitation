<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Vendor;
use App\VendorStatus;

test('a saved vendor is announced only in the vendors section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->followingRedirects()
        ->post(route('events.vendors.store', $event), [
            'name' => 'Asha Kitchen',
            'category' => 'Catering',
            'phone' => '0712000000',
            'price' => 250000,
            'status' => VendorStatus::Shortlisted->value,
        ])
        ->assertOk()
        ->assertSee('data-vendor-notice', false)
        ->assertSee(__('Vendor saved.'), false)
        ->assertDontSee('role="status" data-emca-dismiss', false);
});

test('a host can edit and save a vendor', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $vendor = $event->vendors()->create([
        'name' => 'Asha Kitchen',
        'category' => 'Catering',
        'phone' => '0712000000',
        'price' => 250000,
        'status' => VendorStatus::Shortlisted,
        'notes' => 'Lunch',
    ]);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('data-emca-confirm="'.e(__('Remove this vendor?')).'"', false)
        ->assertSee(__('Save vendor'), false);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->patch(route('events.vendors.update', [$event, $vendor]), [
            'name' => 'Asha Catering',
            'category' => 'Catering',
            'phone' => '0712000001',
            'price' => 300000,
            'status' => VendorStatus::Booked->value,
            'notes' => 'Dinner',
        ])
        ->assertRedirect(route('events.show', $event).'#vendors')
        ->assertSessionHas('vendor_notice', __('Vendor updated.'))
        ->assertSessionMissing('status');

    expect($vendor->refresh()->only(['name', 'phone', 'price', 'notes', 'status']))->toBe([
        'name' => 'Asha Catering',
        'phone' => '0712000001',
        'price' => 300000,
        'notes' => 'Dinner',
        'status' => VendorStatus::Booked,
    ]);
});

test('changing a vendor status stays in the vendors section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $vendor = $event->vendors()->create([
        'name' => 'Asha Kitchen',
        'category' => 'Catering',
        'status' => VendorStatus::Shortlisted,
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->patch(route('events.vendors.update', [$event, $vendor]), [
            'status' => VendorStatus::Booked->value,
        ])
        ->assertRedirect(route('events.show', $event).'#vendors')
        ->assertSessionHas('vendor_notice', __('Vendor updated.'))
        ->assertSessionMissing('status');

    expect($vendor->refresh()->status)->toBe(VendorStatus::Booked);
    expect($vendor->name)->toBe('Asha Kitchen');
});

test('another host cannot change a vendor', function () {
    $event = Event::factory()->create();
    $vendor = $event->vendors()->create([
        'name' => 'Asha Kitchen',
        'category' => 'Catering',
        'status' => VendorStatus::Shortlisted,
    ]);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->patch(route('events.vendors.update', [$event, $vendor]), [
            'name' => 'Changed',
            'category' => 'Music',
            'status' => VendorStatus::Declined->value,
        ])
        ->assertNotFound();

    expect($vendor->refresh()->name)->toBe('Asha Kitchen');
});

test('removing a vendor keeps the notice in the vendors section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $vendor = $event->vendors()->create([
        'name' => 'Asha Kitchen',
        'category' => 'Catering',
        'status' => VendorStatus::Shortlisted,
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->delete(route('events.vendors.destroy', [$event, $vendor]))
        ->assertRedirect(route('events.show', $event).'#vendors')
        ->assertSessionHas('vendor_notice', __('Vendor removed.'))
        ->assertSessionMissing('status');

    expect(Vendor::query()->whereKey($vendor->id)->exists())->toBeFalse();
});
