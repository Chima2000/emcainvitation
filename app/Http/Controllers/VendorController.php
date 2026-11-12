<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\Event;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    use ManagesOwnedEvent;

    public function store(StoreVendorRequest $request, Event $event): RedirectResponse
    {
        $event->vendors()->create($request->validated());

        return back()->with('status', __('Vendor saved.'));
    }

    public function update(UpdateVendorRequest $request, Event $event, Vendor $vendor): RedirectResponse
    {
        $this->ownedChild($event, $vendor);

        $vendor->update($request->validated());

        return back()->with('status', __('Vendor updated.'));
    }

    public function destroy(Request $request, Event $event, Vendor $vendor): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $vendor);

        $vendor->delete();

        return back()->with('status', __('Vendor removed.'));
    }
}
