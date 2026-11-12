<?php

namespace App\Http\Controllers;

use App\BudgetKind;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Pledge;
use App\PledgeStatus;
use App\RsvpStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $eventIds = $user->events()->pluck('id');
        $monthStart = now()->startOfMonth()->subMonths(5);

        $replies = $this->replyCounts($eventIds);
        $guestTotal = (int) $replies->sum('total');
        $attending = (int) ($replies[RsvpStatus::Attending->value]['total'] ?? 0);
        $declined = (int) ($replies[RsvpStatus::Declined->value]['total'] ?? 0);
        $pending = (int) ($replies[RsvpStatus::Pending->value]['total'] ?? 0);
        $checkedIn = Guest::query()
            ->whereIn('event_id', $eventIds)
            ->whereNotNull('checked_in_at')
            ->count();
        $pledged = (int) Pledge::query()
            ->whereIn('event_id', $eventIds)
            ->where('status', PledgeStatus::Received)
            ->sum('amount');
        $budgetedExpenses = (int) BudgetItem::query()
            ->whereIn('event_id', $eventIds)
            ->where('kind', BudgetKind::Expense)
            ->sum('amount');

        $guestsByMonth = Guest::query()
            ->whereIn('event_id', $eventIds)
            ->where('created_at', '>=', $monthStart)
            ->get(['created_at', 'rsvp_status'])
            ->groupBy(fn (Guest $guest): string => $guest->created_at->format('Y-m'));

        $checkInsByMonth = Guest::query()
            ->whereIn('event_id', $eventIds)
            ->where('checked_in_at', '>=', $monthStart)
            ->get(['checked_in_at'])
            ->countBy(fn (Guest $guest): string => $guest->checked_in_at->format('Y-m'));

        $pledgesByMonth = Pledge::query()
            ->whereIn('event_id', $eventIds)
            ->where('status', PledgeStatus::Received)
            ->where('created_at', '>=', $monthStart)
            ->get(['created_at', 'amount'])
            ->groupBy(fn (Pledge $pledge): string => $pledge->created_at->format('Y-m'))
            ->map(fn (Collection $pledges): int => (int) $pledges->sum('amount'));

        $eventsByMonth = $user->events()
            ->where('created_at', '>=', $monthStart)
            ->get(['created_at'])
            ->countBy(fn (Event $event): string => $event->created_at->format('Y-m'));

        $months = collect(range(0, 5))->map(function (int $offset) use ($monthStart, $guestsByMonth, $checkInsByMonth, $pledgesByMonth, $eventsByMonth): array {
            $month = $monthStart->copy()->addMonths($offset);
            $key = $month->format('Y-m');
            $guests = $guestsByMonth[$key] ?? collect();

            return [
                'key' => $key,
                'label' => $month->translatedFormat('M'),
                'events' => (int) ($eventsByMonth[$key] ?? 0),
                'guests' => $guests->count(),
                'attending' => $guests->where('rsvp_status', RsvpStatus::Attending)->count(),
                'declined' => $guests->where('rsvp_status', RsvpStatus::Declined)->count(),
                'check_ins' => (int) ($checkInsByMonth[$key] ?? 0),
                'pledges' => (int) ($pledgesByMonth[$key] ?? 0),
            ];
        });

        $thisMonth = $months->last();
        $lastMonth = $months->slice(-2, 1)->first();

        $eventTypes = $user->events()
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->orderByDesc('aggregate')
            ->get()
            ->map(function (Event $event): array {
                $type = $event->type;

                return [
                    'label' => $type?->label() ?? __('Other'),
                    'total' => (int) $event->getAttribute('aggregate'),
                ];
            })
            ->values();

        $rsvp = collect([RsvpStatus::Attending, RsvpStatus::Pending, RsvpStatus::Declined])
            ->map(fn (RsvpStatus $status): array => [
                'label' => $status->label(),
                'total' => (int) ($replies[$status->value]['total'] ?? 0),
            ])
            ->values();

        $checkInPercent = $guestTotal === 0 ? 0 : (int) round(($checkedIn / $guestTotal) * 100);

        return view('dashboard.index', [
            'events' => $user->events()
                ->withCount('guests')
                ->withSum(['budgetItems as expense_total' => fn ($query) => $query->where('kind', BudgetKind::Expense)], 'amount')
                ->latest('event_date')
                ->take(6)
                ->get(),
            'recentGuests' => Guest::query()->whereIn('event_id', $eventIds)->with('event:id,title')->latest()->limit(6)->get(),
            'eventTypes' => $eventTypes,
            'rsvp' => $rsvp,
            'stats' => [
                'events' => $eventIds->count(),
                'upcoming_events' => $user->events()->where('event_date', '>=', now()->startOfDay())->count(),
                'guests' => $guestTotal,
                'attending' => $attending,
                'attending_seats' => (int) ($replies[RsvpStatus::Attending->value]['seats'] ?? 0),
                'declined' => $declined,
                'pending' => $pending,
                'replied_percent' => $guestTotal === 0 ? 0 : (int) round((($attending + $declined) / $guestTotal) * 100),
                'checked_in' => $checkedIn,
                'check_in_percent' => $checkInPercent,
                'pledged' => $pledged,
                'pledged_this_month' => $thisMonth['pledges'],
                'budgeted_expenses' => $budgetedExpenses,
            ],
            'trends' => [
                'events' => $this->percentChange($thisMonth['events'], $lastMonth['events']),
                'guests' => $this->percentChange($thisMonth['guests'], $lastMonth['guests']),
                'attending' => $this->percentChange($thisMonth['attending'], $lastMonth['attending']),
                'check_ins' => $this->percentChange($thisMonth['check_ins'], $lastMonth['check_ins']),
                'pledges' => $this->percentChange($thisMonth['pledges'], $lastMonth['pledges']),
            ],
            'chart' => [
                'activity' => [
                    'categories' => $months->pluck('label'),
                    'series' => [
                        ['name' => __('Guests added'), 'data' => $months->pluck('guests')],
                        ['name' => __('Attending'), 'data' => $months->pluck('attending')],
                        ['name' => __('Declined'), 'data' => $months->pluck('declined')],
                    ],
                ],
                'replies' => [
                    'labels' => $rsvp->pluck('label'),
                    'data' => $rsvp->pluck('total'),
                    'centerLabel' => __('Guests'),
                ],
                'types' => [
                    'labels' => $eventTypes->pluck('label'),
                    'data' => $eventTypes->pluck('total'),
                    'centerLabel' => __('Events'),
                ],
                'checkIn' => ['percent' => $checkInPercent],
                'pledges' => [
                    'categories' => $months->pluck('label'),
                    'data' => $months->pluck('pledges'),
                    'name' => __('Pledges received'),
                    'prefix' => 'TZS ',
                ],
            ],
            'period' => $this->period(now()),
        ]);
    }

    /**
     * @param  Collection<int, int>  $eventIds
     * @return Collection<string, array{total: int, seats: int}>
     */
    private function replyCounts(Collection $eventIds): Collection
    {
        return Guest::query()
            ->whereIn('event_id', $eventIds)
            ->selectRaw('rsvp_status, count(*) as total, coalesce(sum(party_size), 0) as seats')
            ->groupBy('rsvp_status')
            ->get()
            ->mapWithKeys(function (Guest $guest): array {
                $status = $guest->rsvp_status instanceof RsvpStatus
                    ? $guest->rsvp_status->value
                    : (string) $guest->rsvp_status;

                return [$status => [
                    'total' => (int) $guest->getAttribute('total'),
                    'seats' => (int) $guest->getAttribute('seats'),
                ]];
            });
    }

    private function percentChange(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function period(Carbon $date): string
    {
        return $date->copy()->startOfMonth()->translatedFormat('D, M d, Y').' – '.$date->copy()->endOfMonth()->translatedFormat('D, M d, Y');
    }
}
