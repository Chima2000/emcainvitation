<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\StoreEventTaskRequest;
use App\Http\Requests\UpdateEventTaskRequest;
use App\Models\Event;
use App\Models\EventTask;
use App\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventTaskController extends Controller
{
    use ManagesOwnedEvent;

    public function store(StoreEventTaskRequest $request, Event $event): RedirectResponse
    {
        $event->tasks()->create([
            ...$request->safe()->except(['status']),
            'status' => $request->enum('status', TaskStatus::class) ?? TaskStatus::Open,
        ]);

        return back()->with('status', __('Task added.'));
    }

    public function update(UpdateEventTaskRequest $request, Event $event, EventTask $task): RedirectResponse
    {
        $this->ownedChild($event, $task);

        $task->update($request->validated());

        return back()->with('status', __('Task updated.'));
    }

    public function destroy(Request $request, Event $event, EventTask $task): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $task);

        $task->delete();

        return back()->with('status', __('Task removed.'));
    }
}
