<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePricingRequest;
use App\Models\ServiceRate;
use App\ServiceItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PricingController extends Controller
{
    public function edit(): View
    {
        return view('admin.pricing.edit', [
            'items' => ServiceItem::cases(),
            'rates' => ServiceRate::table(),
            'market' => [
                ServiceItem::Card->value => 2200,
                ServiceItem::Whatsapp->value => 1000,
                ServiceItem::Sms->value => 100,
            ],
        ]);
    }

    public function update(UpdatePricingRequest $request): RedirectResponse
    {
        foreach (ServiceItem::cases() as $item) {
            ServiceRate::query()->updateOrCreate(
                ['item' => $item],
                ['amount' => (int) $request->validated('rates.'.$item->value)],
            );
        }

        return redirect()
            ->route('admin.pricing.edit')
            ->with('status', __('Prices saved. New messages use these rates straight away.'));
    }
}
