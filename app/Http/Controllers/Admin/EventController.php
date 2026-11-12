<?php

namespace App\Http\Controllers\Admin;

use App\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\PledgeStatus;
use App\RsvpStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $status = EventStatus::tryFrom((string) $request->query('status', ''));
        $when = in_array($request->query('when'), ['upcoming', 'past'], true) ? $request->query('when') : null;
        $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->string('q')->trim()->toString());

        return view('admin.events.index', [
            'events' => Event::query()
                ->with(['user:id,name,phone', 'package:id,name'])
                ->withCount([
                    'guests',
                    'guests as checked_in_count' => fn ($query) => $query->whereNotNull('checked_in_at'),
                ])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($when === 'upcoming', fn ($query) => $query->where('event_date', '>=', now()->startOfDay())->reorder()->orderBy('event_date'))
                ->when($when === 'past', fn ($query) => $query->where('event_date', '<', now()->startOfDay()))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($inner) use ($search): void {
                        $inner->where('title', 'like', "%{$search}%")
                            ->orWhere('venue', 'like', "%{$search}%")
                            ->orWhere('host_name', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when($when !== 'upcoming', fn ($query) => $query->latest('event_date'))
                ->paginate(20)
                ->withQueryString(),
            'statuses' => EventStatus::cases(),
            'status' => $status,
            'when' => $when,
        ]);
    }

    public function show(Event $event): View
    {
        $event->load(['user', 'template', 'package']);
        $event->loadCount([
            'guests',
            'guests as attending_count' => fn ($query) => $query->where('rsvp_status', RsvpStatus::Attending),
            'guests as declined_count' => fn ($query) => $query->where('rsvp_status', RsvpStatus::Declined),
            'guests as pending_count' => fn ($query) => $query->where('rsvp_status', RsvpStatus::Pending),
            'guests as checked_in_count' => fn ($query) => $query->whereNotNull('checked_in_at'),
            'messages',
        ]);

        return view('admin.events.show', [
            'event' => $event,
            'guests' => $event->guests()->latest()->paginate(15, ['*'], 'guests')->withQueryString(),
            'messages' => $event->messages()->with('guest:id,name')->latest()->limit(10)->get(),
            'charges' => $event->walletTransactions()->latest()->get(),
            'spent' => (int) -$event->walletTransactions()->sum('amount'),
            'pledged' => (int) $event->pledges()->where('status', PledgeStatus::Received)->sum('amount'),
        ]);
    }
}
