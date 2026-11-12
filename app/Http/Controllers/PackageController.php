<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\ServiceRate;
use App\ServiceItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        $rates = ServiceRate::table();

        return view('packages.index', [
            'packages' => Package::query()->active()->get(),
            'events' => $request->user()
                ->events()
                ->with('package:id,name')
                ->withCount('guests')
                ->where('event_date', '>=', now()->startOfDay())
                ->orderBy('event_date')
                ->get(),
            'selectedEventId' => $request->integer('event') ?: null,
            'balance' => $request->user()->wallet_balance,
            'rates' => $rates,
            'items' => ServiceItem::cases(),
            'payAsYouGoPerGuest' => $rates[ServiceItem::Card->value] + $rates[ServiceItem::Whatsapp->value],
        ]);
    }
}
