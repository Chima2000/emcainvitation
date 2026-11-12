<?php

namespace App\Http\Controllers;

use App\Actions\PurchasePackage;
use App\Http\Requests\PurchasePackageRequest;
use App\Models\Event;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;

class EventPackageController extends Controller
{
    public function store(PurchasePackageRequest $request, Event $event, PurchasePackage $purchasePackage): RedirectResponse
    {
        $package = Package::query()->findOrFail($request->integer('package_id'));

        $charged = $purchasePackage->handle($event, $package);

        return redirect()
            ->route('events.show', $event)
            ->with('status', __(':package is active for this event. TZS :amount was paid from your wallet.', [
                'package' => $package->name,
                'amount' => number_format($charged),
            ]));
    }
}
