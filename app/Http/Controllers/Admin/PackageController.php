<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PackageRequest;
use App\MessageChannel;
use App\Models\Package;
use App\Models\ServiceRate;
use App\ServiceItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PackageController extends Controller
{
    public function index(): View
    {
        $rates = ServiceRate::table();

        return view('admin.packages.index', [
            'packages' => Package::query()
                ->withCount('events')
                ->withSum('events as revenue', 'package_price_paid')
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get(),
            'channels' => MessageChannel::cases(),
            'payAsYouGoPerGuest' => $rates[ServiceItem::Card->value] + $rates[ServiceItem::Whatsapp->value],
        ]);
    }

    public function store(PackageRequest $request): RedirectResponse
    {
        Package::query()->create($request->packageAttributes());

        return redirect()
            ->route('admin.packages.index')
            ->with('status', __('Package saved.'));
    }

    public function update(PackageRequest $request, Package $package): RedirectResponse
    {
        $package->update($request->packageAttributes());

        return redirect()
            ->route('admin.packages.index')
            ->with('status', __('Package updated. Events that already bought it keep what they paid for.'));
    }

    public function destroy(Package $package): RedirectResponse
    {
        if ($package->events()->exists()) {
            $package->update(['is_active' => false]);

            return redirect()
                ->route('admin.packages.index')
                ->with('status', __('Events already use this package, so it was hidden from hosts instead of deleted.'));
        }

        $package->delete();

        return redirect()
            ->route('admin.packages.index')
            ->with('status', __('Package deleted.'));
    }
}
