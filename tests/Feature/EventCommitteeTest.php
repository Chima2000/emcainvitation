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
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('data-emca-confirm="'.e(__('Remove this committee? Its budget lines will stay in the event budget.')).'"', false);

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
        ->assertRedirect(route('events.show', $event).'#budget')
        ->assertSessionHasErrors('committee_id');

    $this->actingAs($host)
        ->from(route('events.show', $event))
        ->post(route('events.budget-items.store', $event), [
            'kind' => BudgetKind::Expense->value,
            'label' => 'Uncategorized food',
            'amount' => 10000,
            'committee_id' => $committee->id,
        ])
        ->assertRedirect(route('events.show', $event).'#budget')
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

    $this->actingAs($otherHost)
        ->post(route('events.committees.guests.store', [$event, $committee]), [
            'guest_ids' => [$guest->id],
        ])
        ->assertNotFound();

    $this->assertDatabaseHas('committees', [
        'id' => $committee->id,
        'name' => 'Food Committee',
    ]);
});

test('the guest CSV template matches the guest fields and an import keeps notes', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $download = $this->actingAs($host)->get(route('events.guests.template', $event));

    $download->assertOk();
    expect($download->streamedContent())->toContain('name,phone,email,gender,party_size,notes');

    $this->post(route('events.guests.import', $event), [
        'file' => UploadedFile::fake()->createWithContent(
            'guests.csv',
            "notes,name,phone,email,gender,party_size\nFamily,Asha Mushi,0712345678,asha@example.com,female,2\n",
        ),
    ])->assertRedirect(route('events.show', $event));

    $this->assertDatabaseHas('guests', [
        'event_id' => $event->id,
        'name' => 'Asha Mushi',
        'notes' => 'Family',
        'gender' => GuestGender::Female->value,
    ]);
});

test('the guest list shows email and gender in their own columns', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create([
        'name' => 'Asha Mushi',
        'phone' => '0712345678',
        'email' => 'asha.mushi@example.com',
        'gender' => GuestGender::Female,
    ]);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('<th>'.e(__('Email')).'</th>', false)
        ->assertSee('<th>'.e(__('Gender')).'</th>', false)
        ->assertSee('<th class="text-center">'.e(__('Action')).'</th>', false)
        ->assertSee('fi-rr-menu-dots', false)
        ->assertSee('href="https://wa.me/', false)
        ->assertSee('>SMS</a>', false)
        ->assertSee('data-copy="', false)
        ->assertSee('>'.e(__('Edit')).'</a>', false)
        ->assertSee('class="dropdown-item text-danger"', false)
        ->assertSee('asha.mushi@example.com')
        ->assertSee(GuestGender::Female->label())
        ->assertSee('0712345678');
});

test('a guest added later stays off an existing committee until the host adds them there', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    $leader = Guest::factory()->for($event)->create(['name' => 'Asha Mushi']);
    $newGuest = Guest::factory()->for($event)->create(['name' => 'Neema Lyimo']);
    $otherEvent = Event::factory()->for($host)->create();
    $otherGuest = Guest::factory()->for($otherEvent)->create();
    $committee = $event->committees()->create([
        'name' => 'Food Committee',
        'leader_guest_id' => $leader->id,
    ]);
    $committee->guests()->sync([$leader->id]);

    $page = $this->actingAs($host)->get(route('events.show', $event));

    $page->assertOk()
        ->assertSee('col-12 col-lg-6', false)
        ->assertSee('emca-fit-name', false)
        ->assertSeeInOrder([
            'form="save-committee-'.$committee->id.'"',
            'data-committee-add-toggle="add-committee-'.$committee->id.'"',
        ], false)
        ->assertSee(__('Add guests'))
        ->assertSee(__('Edit committee'))
        ->assertSee('data-committee-open-label="'.e(__('Close')).'"', false);
    $members = str($page->getContent())->between(
        'data-committee-members="'.$committee->id.'"',
        'data-committee-add="'.$committee->id.'"',
    );
    expect($members->toString())->toContain('Asha Mushi')->not->toContain('Neema Lyimo');
    expect($page->getContent())->toMatch('/id="add-committee-'.$committee->id.'" data-committee-add="'.$committee->id.'"\s+hidden/');
    expect($page->getContent())->toContain('add-committee-'.$committee->id.'-participant-'.$newGuest->id);

    $this->post(route('events.committees.guests.store', [$event, $committee]), [
        'guest_ids' => [$newGuest->id],
    ])->assertRedirect(route('events.show', $event).'#committees');

    $this->assertDatabaseHas('committee_guest', [
        'committee_id' => $committee->id,
        'guest_id' => $leader->id,
    ]);
    $this->assertDatabaseHas('committee_guest', [
        'committee_id' => $committee->id,
        'guest_id' => $newGuest->id,
    ]);

    $this->from(route('events.show', $event))
        ->post(route('events.committees.guests.store', [$event, $committee]), [
            'guest_ids' => [$otherGuest->id],
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors('participant_ids.0');

    $this->assertDatabaseMissing('committee_guest', [
        'committee_id' => $committee->id,
        'guest_id' => $otherGuest->id,
    ]);
});

test('committee members and the leader are chosen with checkboxes', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();
    Guest::factory()->for($event)->create(['name' => 'Asha Mushi']);

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee(__('Committee and participants'))
        ->assertSee('name="participant_ids[]"', false)
        ->assertSee('name="leader_guest_id"', false)
        ->assertSee('type="checkbox"', false);
});

test('saved event records start hidden behind a show button', function () {
    $host = User::factory()->create();
    $event = Event::factory()->for($host)->create();

    $this->actingAs($host)
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee(__('Show committees'))
        ->assertSee(__('Show pledges'))
        ->assertSee(__('Show budget'))
        ->assertSee(__('Show tasks'))
        ->assertSee(__('Show vendors'))
        ->assertSee('id="committee-list" hidden', false)
        ->assertSee('id="pledge-list" hidden', false)
        ->assertSee('id="budget-list" hidden', false)
        ->assertSee('id="task-list" hidden', false)
        ->assertSee('id="vendor-list" hidden', false);
});
