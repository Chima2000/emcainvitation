<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\Event;
use App\Models\Guest;
use App\Models\OutboundMessage;
use App\Models\Package;
use App\Models\TopUpRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use App\RsvpStatus;
use App\TopUpStatus;
use App\UserRole;
use App\WalletEntryType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth()->subMonths(5);

        $replyCounts = Guest::query()
            ->selectRaw('rsvp_status, count(*) as total')
            ->groupBy('rsvp_status')
            ->get()
            ->mapWithKeys(fn (Guest $guest): array => [
                ($guest->rsvp_status instanceof RsvpStatus ? $guest->rsvp_status->value : (string) $guest->rsvp_status) => (int) $guest->getAttribute('total'),
            ]);

        $guestTotal = (int) $replyCounts->sum();
        $attending = (int) ($replyCounts[RsvpStatus::Attending->value] ?? 0);
        $declined = (int) ($replyCounts[RsvpStatus::Declined->value] ?? 0);

        $eventTypes = Event::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get()
            ->map(function (Event $event): array {
                $type = $event->type;

                return [
                    'label' => $type?->label() ?? __('Other'),
                    'total' => (int) $event->getAttribute('total'),
                ];
            })
            ->values();

        $rsvp = collect([RsvpStatus::Attending, RsvpStatus::Pending, RsvpStatus::Declined])
            ->map(fn (RsvpStatus $status): array => [
                'label' => $status->label(),
                'total' => (int) ($replyCounts[$status->value] ?? 0),
            ])
            ->values();

        $eventsByMonth = $this->countByMonth(Event::query()->where('created_at', '>=', $monthStart)->get(['created_at']));
        $guestsByMonth = $this->countByMonth(Guest::query()->where('created_at', '>=', $monthStart)->get(['created_at']));
        $attendingByMonth = $this->countByMonth(Guest::query()->where('created_at', '>=', $monthStart)->where('rsvp_status', RsvpStatus::Attending)->get(['created_at']));
        $hostsByMonth = $this->countByMonth(User::query()->where('role', UserRole::Host)->where('created_at', '>=', $monthStart)->get(['created_at']));
        $demosByMonth = $this->countByMonth(DemoRequest::query()->where('created_at', '>=', $monthStart)->get(['created_at']));

        $ledger = WalletTransaction::query()
            ->where('created_at', '>=', $monthStart)
            ->get(['type', 'amount', 'created_at'])
            ->groupBy(fn (WalletTransaction $entry): string => $entry->created_at->format('Y-m'));

        $months = collect(range(0, 5))->map(function (int $offset) use ($monthStart, $eventsByMonth, $guestsByMonth, $attendingByMonth, $hostsByMonth, $demosByMonth, $ledger): array {
            $month = $monthStart->copy()->addMonths($offset);
            $key = $month->format('Y-m');
            $entries = $ledger[$key] ?? collect();

            return [
                'label' => $month->translatedFormat('M'),
                'events' => (int) ($eventsByMonth[$key] ?? 0),
                'guests' => (int) ($guestsByMonth[$key] ?? 0),
                'attending' => (int) ($attendingByMonth[$key] ?? 0),
                'hosts' => (int) ($hostsByMonth[$key] ?? 0),
                'demos' => (int) ($demosByMonth[$key] ?? 0),
                'package_sales' => (int) -$entries->where('type', WalletEntryType::PackagePurchase)->sum('amount'),
                'message_charges' => (int) -$entries->where('type', WalletEntryType::MessageCharge)->sum('amount'),
                'top_ups' => (int) $entries->where('type', WalletEntryType::TopUp)->sum('amount'),
                'subscriptions' => (int) -$entries->where('type', WalletEntryType::Subscription)->sum('amount'),
            ];
        });

        $thisMonth = $months->last();
        $lastMonth = $months->slice(-2, 1)->first();
        $attendingPercent = $guestTotal === 0 ? 0 : (int) round(($attending / $guestTotal) * 100);
        $revenueThisMonth = $thisMonth['package_sales'] + $thisMonth['message_charges'] + $thisMonth['subscriptions'];
        $revenueLastMonth = $lastMonth['package_sales'] + $lastMonth['message_charges'] + $lastMonth['subscriptions'];

        $pendingTopUps = TopUpRequest::query()->where('status', TopUpStatus::Pending);
        $approvedThisMonth = (int) TopUpRequest::query()
            ->where('status', TopUpStatus::Approved)
            ->where('reviewed_at', '>=', now()->startOfMonth())
            ->sum('amount');
        $pendingAmount = (int) (clone $pendingTopUps)->sum('amount');
        $collectionPercent = ($approvedThisMonth + $pendingAmount) === 0
            ? 0
            : (int) round($approvedThisMonth / ($approvedThisMonth + $pendingAmount) * 100);

        return view('admin.dashboard', [
            'stats' => [
                'hosts' => User::query()->where('role', UserRole::Host)->count(),
                'hosts_this_month' => $thisMonth['hosts'],
                'events' => Event::query()->count(),
                'guests' => $guestTotal,
                'demos' => DemoRequest::query()->count(),
                'attending' => $attending,
                'declined' => $declined,
                'pending' => (int) ($replyCounts[RsvpStatus::Pending->value] ?? 0),
                'attending_percent' => $attendingPercent,
                'replied_percent' => $guestTotal === 0 ? 0 : (int) round((($attending + $declined) / $guestTotal) * 100),
                'upcoming_events' => Event::query()->where('event_date', '>=', now()->startOfDay())->count(),
                'events_this_month' => $thisMonth['events'],
                'revenue_month' => $revenueThisMonth,
                'revenue_total' => (int) -WalletTransaction::query()
                    ->whereIn('type', WalletEntryType::revenueTypes())
                    ->sum('amount'),
                'wallet_float' => (int) User::query()->where('role', UserRole::Host)->sum('wallet_balance'),
                'pending_top_ups' => (clone $pendingTopUps)->count(),
                'pending_top_up_amount' => $pendingAmount,
                'top_ups_month' => $approvedThisMonth,
                'collection_percent' => $collectionPercent,
                'packages_sold' => Event::query()->whereNotNull('package_purchased_at')->count(),
                'messages_month' => OutboundMessage::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'trends' => [
                'hosts' => $this->percentChange($thisMonth['hosts'], $lastMonth['hosts']),
                'events' => $this->percentChange($thisMonth['events'], $lastMonth['events']),
                'guests' => $this->percentChange($thisMonth['guests'], $lastMonth['guests']),
                'attending' => $this->percentChange($thisMonth['attending'], $lastMonth['attending']),
                'demos' => $this->percentChange($thisMonth['demos'], $lastMonth['demos']),
                'revenue' => $this->percentChange($revenueThisMonth, $revenueLastMonth),
                'top_ups' => $this->percentChange($thisMonth['top_ups'], $lastMonth['top_ups']),
            ],
            'hosts' => User::query()
                ->where('role', UserRole::Host)
                ->withCount('events')
                ->latest()
                ->limit(6)
                ->get(),
            'newHosts' => User::query()
                ->where('role', UserRole::Host)
                ->where('created_at', '>=', now()->startOfMonth())
                ->latest()
                ->limit(4)
                ->get(['id', 'name']),
            'events' => Event::query()->with(['user:id,name', 'package:id,name'])->withCount('guests')->latest()->limit(6)->get(),
            'upcomingEvents' => Event::query()
                ->with('user:id,name')
                ->where('event_date', '>=', now()->startOfDay())
                ->orderBy('event_date')
                ->limit(5)
                ->get(),
            'demos' => DemoRequest::query()->latest()->limit(5)->get(),
            'pendingTopUps' => (clone $pendingTopUps)->with('user:id,name,phone')->oldest()->limit(5)->get(),
            'recentTransactions' => WalletTransaction::query()->with('user:id,name')->latest()->limit(6)->get(),
            'topPackages' => Package::query()
                ->withCount('events')
                ->orderByDesc('events_count')
                ->orderBy('sort_order')
                ->limit(4)
                ->get(),
            'eventTypes' => $eventTypes,
            'rsvp' => $rsvp,
            'chart' => [
                'activity' => [
                    'categories' => $months->pluck('label'),
                    'series' => [
                        ['name' => __('Events opened'), 'data' => $months->pluck('events')],
                        ['name' => __('New hosts'), 'data' => $months->pluck('hosts')],
                        ['name' => __('Demo requests'), 'data' => $months->pluck('demos')],
                    ],
                ],
                'revenue' => [
                    'categories' => $months->pluck('label'),
                    'series' => [
                        ['name' => __('Package sales'), 'data' => $months->pluck('package_sales')],
                        ['name' => __('Message charges'), 'data' => $months->pluck('message_charges')],
                    ],
                    'prefix' => 'TZS ',
                ],
                'revenueSpark' => [
                    'categories' => $months->pluck('label'),
                    'data' => $months->map(fn (array $month): int => $month['package_sales'] + $month['message_charges']),
                    'name' => __('Revenue'),
                    'prefix' => 'TZS ',
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
                'attendance' => ['percent' => $attendingPercent],
                'collections' => ['percent' => $collectionPercent],
                'guests' => [
                    'categories' => $months->pluck('label'),
                    'data' => $months->pluck('guests'),
                    'name' => __('Guests added'),
                ],
                'topUps' => [
                    'categories' => $months->pluck('label'),
                    'data' => $months->pluck('top_ups'),
                    'name' => __('Top-ups'),
                    'prefix' => 'TZS ',
                ],
            ],
            'period' => now()->startOfMonth()->translatedFormat('D, M d, Y').' – '.now()->endOfMonth()->translatedFormat('D, M d, Y'),
        ]);
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return Collection<string, int>
     */
    private function countByMonth(Collection $records): Collection
    {
        return $records->countBy(fn (Model $record): string => $record->created_at->format('Y-m'));
    }

    private function percentChange(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}
