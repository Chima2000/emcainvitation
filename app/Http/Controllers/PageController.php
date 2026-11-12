<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\ServiceRate;
use App\ServiceItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class PageController extends Controller
{
    public function features(): View
    {
        return view('pages.features');
    }

    public function pricing(): View
    {
        return view('pages.pricing', [
            'packages' => Package::query()->active()->get(),
            'rates' => ServiceRate::table(),
            'items' => ServiceItem::cases(),
        ]);
    }

    public function privacy(): View
    {
        return view('pages.privacy', [
            'updatedAt' => Carbon::parse(config('emca.legal_updated_at')),
        ]);
    }

    public function terms(): View
    {
        return view('pages.terms', [
            'updatedAt' => Carbon::parse(config('emca.legal_updated_at')),
        ]);
    }
}
