<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Package;
use App\WalletEntryType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchasePackage
{
    public function __construct(private RecordWalletEntry $recordWalletEntry) {}

    /**
     * Pay for a package from the host wallet. Upgrades only charge the difference.
     */
    public function handle(Event $event, Package $package): int
    {
        if (! $package->is_active) {
            throw ValidationException::withMessages([
                'package_id' => __('That package is no longer offered.'),
            ]);
        }

        return DB::transaction(function () use ($event, $package): int {
            $lockedEvent = Event::query()->with(['user', 'package'])->lockForUpdate()->findOrFail($event->id);

            if ($lockedEvent->hasPackage() && $package->price <= $lockedEvent->package_price_paid) {
                throw ValidationException::withMessages([
                    'package_id' => __('This event already has :name. Choose a bigger package to upgrade.', [
                        'name' => $lockedEvent->package?->name ?? __('a package'),
                    ]),
                ]);
            }

            $charge = $package->price - $lockedEvent->package_price_paid;

            $this->recordWalletEntry->handle(
                $lockedEvent->user,
                WalletEntryType::PackagePurchase,
                -$charge,
                $lockedEvent->hasPackage()
                    ? __('Upgrade to :package for :event', ['package' => $package->name, 'event' => $lockedEvent->title])
                    : __(':package for :event', ['package' => $package->name, 'event' => $lockedEvent->title]),
                ['event_id' => $lockedEvent->id],
            );

            $lockedEvent->forceFill([
                'package_id' => $package->id,
                'package_guest_limit' => $package->guest_limit,
                'package_reminder_rounds' => $package->reminder_rounds,
                'package_includes_thanks' => $package->includes_thanks,
                'package_price_paid' => $package->price,
                'package_purchased_at' => now(),
            ])->save();

            $event->setRawAttributes($lockedEvent->getAttributes(), true);

            return $charge;
        });
    }
}
