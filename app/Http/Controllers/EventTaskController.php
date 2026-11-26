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

        return $this->taskBack(__('Task added.'));
    }

    public function update(UpdateEventTaskRequest $request, Event $event, EventTask $task): RedirectResponse
    {
        $this->ownedChild($event, $task);

        $task->update($request->validated());

        return $this->taskBack(__('Task updated.'));
    }

    public function destroy(Request $request, Event $event, EventTask $task): RedirectResponse
    {
        $this->ownedEvent($request, $event);
        $this->ownedChild($event, $task);

        $task->delete();

        return $this->taskBack(__('Task removed.'));
    }

    private function taskBack(string $message): RedirectResponse
    {
        return back()
            ->withFragment('tasks')
            ->with('task_notice', $message);
    }
}
