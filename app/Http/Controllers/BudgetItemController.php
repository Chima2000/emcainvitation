<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\StoreBudgetItemRequest;
use App\Http\Requests\UpdateBudgetItemRequest;
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

        return $this->budgetBack(__('Budget line saved.'));
    }

    public function update(UpdateBudgetItemRequest $request, Event $event, BudgetItem $budgetItem): RedirectResponse
    {
        $this->ownedChild($event, $budgetItem);

        $budgetItem->update($request->validated());

        return $this->budgetBack(__('Budget line updated.'));
    }

    public function destroy(Request $request, Event $event, BudgetItem $budgetItem): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $budgetItem);

        $budgetItem->delete();

        return $this->budgetBack(__('Budget line removed.'));
    }

    private function budgetBack(string $message): RedirectResponse
    {
        return back()
            ->withFragment('budget')
            ->with('budget_notice', $message);
    }
}
