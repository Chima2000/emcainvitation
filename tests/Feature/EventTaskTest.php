<?php

use App\Models\Event;
use App\Models\EventTask;
use App\Models\User;
use App\TaskStatus;

test('a saved task is announced only in the tasks section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->followingRedirects()
        ->post(route('events.tasks.store', $event), [
            'title' => 'Call the hall',
            'due_on' => now()->toDateString(),
        ])
        ->assertOk()
        ->assertSee('data-task-notice', false)
        ->assertSee(__('Task added.'), false)
        ->assertDontSee('role="status" data-emca-dismiss', false);
});

test('a task due date cannot be before today', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $min = now()->timezone(config('app.timezone'))->toDateString();

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('min="'.$min.'"', false);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.tasks.store', $event), [
            'title' => 'Call the hall',
            'due_on' => now()->subDay()->toDateString(),
        ])
        ->assertRedirect(route('events.show', $event).'#tasks')
        ->assertSessionHasErrors('due_on');

    expect($event->tasks()->count())->toBe(0);
});

test('a host can edit and save a task', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $task = $event->tasks()->create([
        'title' => 'Call the hall',
        'due_on' => now()->toDateString(),
        'status' => TaskStatus::Open,
        'notes' => 'Morning',
    ]);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('data-emca-confirm="'.e(__('Remove this task?')).'"', false)
        ->assertSee(__('Save task'), false);

    $due = now()->addDay()->toDateString();

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->patch(route('events.tasks.update', [$event, $task]), [
            'title' => 'Confirm the hall',
            'due_on' => $due,
            'notes' => 'Afternoon',
        ])
        ->assertRedirect(route('events.show', $event).'#tasks')
        ->assertSessionHas('task_notice', __('Task updated.'))
        ->assertSessionMissing('status');

    expect($task->refresh()->only(['title', 'notes', 'status']))->toBe([
        'title' => 'Confirm the hall',
        'notes' => 'Afternoon',
        'status' => TaskStatus::Open,
    ]);
    expect($task->due_on?->toDateString())->toBe($due);
});

test('marking a task done stays in the tasks section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $task = $event->tasks()->create([
        'title' => 'Call the hall',
        'status' => TaskStatus::Open,
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->patch(route('events.tasks.update', [$event, $task]), [
            'status' => TaskStatus::Done->value,
        ])
        ->assertRedirect(route('events.show', $event).'#tasks')
        ->assertSessionHas('task_notice', __('Task updated.'))
        ->assertSessionMissing('status');

    expect($task->refresh()->status)->toBe(TaskStatus::Done);
});

test('another host cannot change a task', function () {
    $event = Event::factory()->create();
    $task = $event->tasks()->create([
        'title' => 'Call the hall',
        'status' => TaskStatus::Open,
    ]);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->patch(route('events.tasks.update', [$event, $task]), [
            'title' => 'Changed',
        ])
        ->assertNotFound();

    expect($task->refresh()->title)->toBe('Call the hall');
});

test('removing a task keeps the notice in the tasks section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $task = $event->tasks()->create([
        'title' => 'Call the hall',
        'status' => TaskStatus::Open,
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->delete(route('events.tasks.destroy', [$event, $task]))
        ->assertRedirect(route('events.show', $event).'#tasks')
        ->assertSessionHas('task_notice', __('Task removed.'))
        ->assertSessionMissing('status');

    expect(EventTask::query()->whereKey($task->id)->exists())->toBeFalse();
});
