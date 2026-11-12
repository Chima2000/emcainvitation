<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\StoreBudgetItemRequest;
use App\Models\BudgetItem;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BudgetItemController extends Controller
{
    use ManagesOwnedEvent;

    public function store(StoreBudgetItemRequest $request, Event $event): RedirectResponse
    {
        $event->budgetItems()->create($request->validated());

        return back()->with('status', __('Budget line saved.'));
    }

    public function destroy(Request $request, Event $event, BudgetItem $budgetItem): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $budgetItem);

        $budgetItem->delete();

        return back()->with('status', __('Budget line removed.'));
    }
}
