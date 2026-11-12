<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\StorePledgeRequest;
use App\Http\Requests\UpdatePledgeRequest;
use App\Models\Event;
use App\Models\Pledge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PledgeController extends Controller
{
    use ManagesOwnedEvent;

    public function store(StorePledgeRequest $request, Event $event): RedirectResponse
    {
        $event->pledges()->create($request->validated());

        return back()->with('status', __('Pledge recorded.'));
    }

    public function update(UpdatePledgeRequest $request, Event $event, Pledge $pledge): RedirectResponse
    {
        $this->ownedChild($event, $pledge);

        $pledge->update($request->validated());

        return back()->with('status', __('Pledge updated.'));
    }

    public function destroy(Request $request, Event $event, Pledge $pledge): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $pledge);

        $pledge->delete();

        return back()->with('status', __('Pledge removed.'));
    }
}
