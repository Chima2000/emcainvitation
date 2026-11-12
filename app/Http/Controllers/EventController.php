<?php

namespace App\Http\Controllers;

use App\Actions\DispatchGuestMessages;
use App\BudgetKind;
use App\EventStatus;
use App\Http\Controllers\Concerns\ManagesOwnedEvent;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\MessageChannel;
use App\MessagePurpose;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\InvitationTemplate;
use App\Models\Package;
use App\PledgeStatus;
use App\RsvpStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ManagesOwnedEvent;

    public function index(Request $request): View
    {
        return view('events.index', [
            'events' => $request->user()
                ->events()
                ->with('template')
                ->withCount('guests')
                ->latest('event_date')
                ->paginate(12),
        ]);
    }

    public function upcoming(): View
    {
        return view('events.upcoming', [
            'events' => Event::query()->upcoming()->with(['template', 'user'])->paginate(12),
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('events.index', array_filter([
            'new' => 1,
            'template' => $request->integer('template') ?: null,
        ]));
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = $request->user()->events()->create([
            ...$request->safe()->except(['is_public', 'organization_name']),
            'is_public' => $request->boolean('is_public'),
            'status' => $request->enum('status', EventStatus::class) ?? EventStatus::Draft,
        ]);

        return redirect()
            ->route('events.show', $event)
            ->with('status', __('Event created. Add guests when you are ready to invite them.'));
    }

    public function show(Request $request, Event $event, DispatchGuestMessages $dispatchGuestMessages): View
    {
        $this->ownedEvent($request, $event);

        $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->string('q')->trim()->toString());

        $guests = $event->guests()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('serial', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $event->load([
            'template',
            'pledges' => fn ($query) => $query->latest(),
            'budgetItems' => fn ($query) => $query->with('committee')->latest(),
            'committees.leader',
            'committees.guests',
            'tasks' => fn ($query) => $query->orderBy('due_on'),
            'vendors' => fn ($query) => $query->latest(),
        ]);

        $event->setRelation(
            'messages',
            $event->messages()->with('guest')->latest()->limit(15)->get(),
        );

        $event->loadCount([
            'guests',
            'guests as attending_count' => fn ($query) => $query->where('rsvp_status', RsvpStatus::Attending),
            'guests as declined_count' => fn ($query) => $query->where('rsvp_status', RsvpStatus::Declined),
            'guests as pending_count' => fn ($query) => $query->where('rsvp_status', RsvpStatus::Pending),
            'guests as checked_in_count' => fn ($query) => $query->whereNotNull('checked_in_at'),
        ]);

        return view('events.show', [
            'event' => $event,
            'guests' => $guests,
            'committeeGuests' => $event->guests()->orderBy('name')->get(['id', 'name', 'phone', 'gender']),
            'recentCheckIns' => $event->guests()->whereNotNull('checked_in_at')->latest('checked_in_at')->limit(6)->get(),
            'income' => (int) $event->budgetItems->where('kind', BudgetKind::Income)->sum('amount'),
            'expense' => (int) $event->budgetItems->where('kind', BudgetKind::Expense)->sum('amount'),
            'receivedPledges' => (int) $event->pledges->where('status', PledgeStatus::Received)->sum('amount'),
            'headcount' => (int) $event->guests()->where('rsvp_status', RsvpStatus::Attending)->sum('party_size'),
            'billing' => $this->billingSummary($request, $event, $dispatchGuestMessages),
        ]);
    }

    /**
     * @return array{balance: int, spent: int, allowance: array<string, int>, prices: array<string, array<string, int>>, upgrades: Collection<int, Package>}
     */
    private function billingSummary(Request $request, Event $event, DispatchGuestMessages $dispatchGuestMessages): array
    {
        $event->loadMissing('package');

        return [
            'balance' => $request->user()->wallet_balance,
            'spent' => (int) -$event->walletTransactions()->sum('amount'),
            'allowance' => collect(MessagePurpose::cases())
                ->mapWithKeys(fn (MessagePurpose $purpose): array => [$purpose->value => $event->packageAllowanceLeft($purpose)])
                ->all(),
            'prices' => collect(MessagePurpose::cases())
                ->mapWithKeys(fn (MessagePurpose $purpose): array => [
                    $purpose->value => collect(MessageChannel::cases())
                        ->mapWithKeys(fn (MessageChannel $channel): array => [$channel->value => $dispatchGuestMessages->unitPrice($purpose, $channel)])
                        ->all(),
                ])
                ->all(),
            'upgrades' => Package::query()
                ->active()
                ->when($event->hasPackage(), fn ($query) => $query->where('price', '>', $event->package_price_paid))
                ->get(),
        ];
    }

    public function edit(Request $request, Event $event): View
    {
        $this->ownedEvent($request, $event);

        return view('events.edit', [
            'event' => $event,
            'templates' => InvitationTemplate::query()->orderBy('name')->get(),
            'types' => EventCategory::options(),
            'statuses' => EventStatus::cases(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->ownedEvent($request, $event);

        $event->update([
            ...$request->safe()->except(['is_public', 'organization_name']),
            'is_public' => $request->boolean('is_public'),
        ]);

        return redirect()
            ->route('events.show', $event)
            ->with('status', __('Event details saved.'));
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()?->can('delete', $event) ?? false, 404);

        $event->delete();

        return redirect()
            ->route('events.index')
            ->with('status', __('Event deleted.'));
    }
}
