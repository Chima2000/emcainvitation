<?php

use App\BudgetKind;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\User;

test('a saved budget line is announced only in the budget section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->followingRedirects()
        ->post(route('events.budget-items.store', $event), [
            'kind' => BudgetKind::Expense->value,
            'label' => 'Catering',
            'amount' => 50000,
        ])
        ->assertOk()
        ->assertSee('data-budget-notice', false)
        ->assertSee(__('Budget line saved.'), false)
        ->assertDontSee('role="status" data-emca-dismiss', false);
});

test('a host can edit and save a budget line', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $item = $event->budgetItems()->create([
        'kind' => BudgetKind::Expense,
        'label' => 'Catering',
        'amount' => 50000,
        'sector' => 'Food',
    ]);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('data-emca-confirm="'.e(__('Remove this budget line?')).'"', false)
        ->assertSee(__('Save budget line'), false);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->put(route('events.budget-items.update', [$event, $item]), [
            'kind' => BudgetKind::Income->value,
            'label' => 'Gifts',
            'amount' => 12000,
        ])
        ->assertRedirect(route('events.show', $event).'#budget')
        ->assertSessionHas('budget_notice', __('Budget line updated.'))
        ->assertSessionMissing('status');

    expect($item->refresh()->only(['label', 'amount', 'kind']))->toBe([
        'label' => 'Gifts',
        'amount' => 12000,
        'kind' => BudgetKind::Income,
    ]);
});

test('another host cannot change a budget line', function () {
    $event = Event::factory()->create();
    $item = $event->budgetItems()->create([
        'kind' => BudgetKind::Expense,
        'label' => 'Catering',
        'amount' => 50000,
    ]);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->put(route('events.budget-items.update', [$event, $item]), [
            'kind' => BudgetKind::Expense->value,
            'label' => 'Changed',
            'amount' => 1,
        ])
        ->assertNotFound();

    expect($item->refresh()->label)->toBe('Catering');
});

test('removing a budget line keeps the notice in the budget section', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $item = $event->budgetItems()->create([
        'kind' => BudgetKind::Expense,
        'label' => 'Catering',
        'amount' => 50000,
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->delete(route('events.budget-items.destroy', [$event, $item]))
        ->assertRedirect(route('events.show', $event).'#budget')
        ->assertSessionHas('budget_notice', __('Budget line removed.'))
        ->assertSessionMissing('status');

    expect(BudgetItem::query()->whereKey($item->id)->exists())->toBeFalse();
});
