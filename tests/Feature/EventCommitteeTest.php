<?php

use App\BudgetKind;
use App\GuestGender;
use App\Models\Committee;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('CSV guest imports retain gender in header and headerless formats', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->post(route('events.guests.import', $event), [
            'file' => UploadedFile::fake()->createWithContent(
                'guests.csv',
                "name,phone,email,gender,party_size\nAsha Mushi,255712345678,asha@example.com,female,2\n",
            ),
        ])
        ->assertRedirect(route('events.show', $event));

    $this->actingAs($host)
        ->post(route('events.guests.import', $event), [
            'file' => UploadedFile::fake()->createWithContent(
                'headerless-guests.csv',
                "Juma Said,255712345677,juma@example.com,male,2\n",
            ),
        ])
        ->assertRedirect(route('events.show', $event));

    $this->actingAs($host)
        ->post(route('events.guests.import', $event), [
            'file' => UploadedFile::fake()->createWithContent(
                'legacy-guests.csv',
                "Baraka Juma,255712345679,baraka@example.com,3\n",
            ),
        ])
        ->assertRedirect(route('events.show', $event));

    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Asha Mushi',
        'gender' => GuestGender::Female->value,
        'party_size' => 2,
    ]);
    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Juma Said',
        'gender' => GuestGender::Male->value,
        'party_size' => 2,
    ]);
    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Baraka Juma',
        'gender' => null,
        'party_size' => 3,
    ]);
});

test('a host can save gender on event guests and invalid gender is rejected', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->post(route('events.guests.store', $event), [
            'name' => 'Asha Mushi',
            'gender' => GuestGender::Female->value,
            'party_size' => 1,
        ])
        ->assertRedirect(route('events.show', $event));

    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Asha Mushi',
        'gender' => GuestGender::Female->value,
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.guests.store', $event), [
            'name' => 'Invalid Gender',
            'gender' => 'unknown',
            'party_size' => 1,
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors('gender');

    $this->assertDatabaseCount('guests', 1);
});

test('a host can create a committee from event guests and its participants remain invite-ready', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $leader = Guest::factory()->for($event)->create(['name' => 'Asha Mushi', 'gender' => GuestGender::Female]);
    $participant = Guest::factory()->for($event)->create(['name' => 'Baraka Juma']);

    $this->actingAs($host)
        ->post(route('events.committees.store', $event), [
            'name' => 'Food Committee',
            'leader_guest_id' => $leader->id,
            'participant_ids' => [$leader->id, $participant->id],
        ])
        ->assertRedirect();

    $committee = Committee::query()->where('event_id', $event->id)->sole();

    $this->assertDatabaseHas('committee_guest', [
        'committee_id' => $committee->id,
        'guest_id' => $participant->id,
    ]);

    $this->get(route('invitations.show', $participant->token))
        ->assertOk()
        ->assertSee($participant->name);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee(__('Committees'))
        ->assertSee('Food Committee')
        ->assertSee(GuestGender::Female->label());
});

test('a committee leader must be a selected participant', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $leader = Guest::factory()->for($event)->create();
    $participant = Guest::factory()->for($event)->create();

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.committees.store', $event), [
            'name' => 'Food Committee',
            'leader_guest_id' => $leader->id,
            'participant_ids' => [$participant->id],
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors([
            'leader_guest_id' => __('The committee leader must also be selected as a participant.'),
        ]);

    $this->assertDatabaseMissing('committees', [
        'event_id' => $event->id,
        'name' => 'Food Committee',
    ]);
});

test('a committee cannot include guests from another event', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $otherEvent = Event::factory()->for($host)->create();
    $guest = Guest::factory()->for($event)->create();
    $otherGuest = Guest::factory()->for($otherEvent)->create();

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.committees.store', $event), [
            'name' => 'Food Committee',
            'leader_guest_id' => $guest->id,
            'participant_ids' => [$guest->id, $otherGuest->id],
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors('participant_ids.1');

    $this->assertDatabaseMissing('committees', [
        'event_id' => $event->id,
        'name' => 'Food Committee',
    ]);
});

test('updating and removing a committee retains its budget lines and clears their committee link', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $leader = Guest::factory()->for($event)->create();
    $newLeader = Guest::factory()->for($event)->create();
    $committee = $event->committees()->create([
        'name' => 'Food Committee',
        'leader_guest_id' => $leader->id,
    ]);
    $committee->guests()->sync([$leader->id]);
    $budgetItem = $event->budgetItems()->create([
        'kind' => BudgetKind::Expense,
        'label' => 'Catering',
        'amount' => 50000,
        'committee_id' => $committee->id,
        'sector' => 'Food',
    ]);

    $this->actingAs($host)
        ->put(route('events.committees.update', [$event, $committee]), [
            'name' => 'Hospitality Committee',
            'leader_guest_id' => $newLeader->id,
            'participant_ids' => [$leader->id, $newLeader->id],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('committees', [
        'id' => $committee->id,
        'name' => 'Hospitality Committee',
        'leader_guest_id' => $newLeader->id,
    ]);
    $this->assertDatabaseHas('committee_guest', [
        'committee_id' => $committee->id,
        'guest_id' => $newLeader->id,
    ]);

    $this->actingAs($host)
        ->delete(route('events.committees.destroy', [$event, $committee]))
        ->assertRedirect();

    $this->assertDatabaseMissing('committees', ['id' => $committee->id]);
    $this->assertDatabaseHas('budget_items', [
        'id' => $budgetItem->id,
        'event_id' => $event->id,
        'committee_id' => null,
        'amount' => 50000,
    ]);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Catering')
        ->assertSee('50,000');
});

test('budget lines must use an event committee and name its sector', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $otherEvent = Event::factory()->for($host)->create();
    $guest = Guest::factory()->for($event)->create();
    $otherGuest = Guest::factory()->for($otherEvent)->create();
    $committee = $event->committees()->create([
        'name' => 'Food Committee',
        'leader_guest_id' => $guest->id,
    ]);
    $otherCommittee = $otherEvent->committees()->create([
        'name' => 'Other Committee',
        'leader_guest_id' => $otherGuest->id,
    ]);

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.budget-items.store', $event), [
            'kind' => BudgetKind::Expense->value,
            'label' => 'Invalid venue',
            'amount' => 10000,
            'committee_id' => $otherCommittee->id,
            'sector' => 'Venue',
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors('committee_id');

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.budget-items.store', $event), [
            'kind' => BudgetKind::Expense->value,
            'label' => 'Uncategorized food',
            'amount' => 10000,
            'committee_id' => $committee->id,
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors('sector');

    $this->actingAs($host)
        ->post(route('events.budget-items.store', $event), [
            'kind' => BudgetKind::Expense->value,
            'label' => 'Catering',
            'amount' => 50000,
            'committee_id' => $committee->id,
            'sector' => 'Food',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('budget_items', [
        'event_id' => $event->id,
        'committee_id' => $committee->id,
        'sector' => 'Food',
        'label' => 'Catering',
        'amount' => 50000,
    ]);
});

test('committee budget expenses appear in the host dashboard without including another host', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create(['title' => 'Host Event']);
    $event->budgetItems()->create([
        'kind' => BudgetKind::Expense,
        'label' => 'Venue',
        'amount' => 75000,
        'sector' => 'Venue',
    ]);

    $otherEvent = Event::factory()->for(User::factory())->create(['title' => 'Other Host Event']);
    $otherEvent->budgetItems()->create([
        'kind' => BudgetKind::Expense,
        'label' => 'Other Venue',
        'amount' => 90000,
    ]);

    $this->actingAs($host)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Planned event costs'))
        ->assertSee('75,000')
        ->assertDontSee('Other Host Event')
        ->assertViewHas('stats', fn (array $stats): bool => $stats['budgeted_expenses'] === 75000)
        ->assertViewHas('events', fn ($events): bool => $events->sole()->expense_total === 75000);
});

test('a host cannot edit a committee belonging to another host', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create();
    $guest = Guest::factory()->for($event)->create();
    $committee = $event->committees()->create([
        'name' => 'Food Committee',
        'leader_guest_id' => $guest->id,
    ]);
    $committee->guests()->attach($guest);
    $otherHost = User::factory()->create();

    $this->actingAs($otherHost)
        ->put(route('events.committees.update', [$event, $committee]), [
            'name' => 'Taken Over',
            'leader_guest_id' => $guest->id,
            'participant_ids' => [$guest->id],
        ])
        ->assertNotFound();

    $this->assertDatabaseHas('committees', [
        'id' => $committee->id,
        'name' => 'Food Committee',
    ]);
});
