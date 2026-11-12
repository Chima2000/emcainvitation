@php
    $menuEvent = request()->route('event');

    if (! $menuEvent instanceof \App\Models\Event && isset($event) && $event instanceof \App\Models\Event) {
        $menuEvent = $event;
    }

    if (! $menuEvent instanceof \App\Models\Event) {
        $menuEvent = null;
    }

    $eventSections = [
        'guests' => __('Guests'),
        'check-in' => __('Door'),
        'messages' => __('Messages'),
        'pledges' => __('Pledges'),
        'budget' => __('Budget'),
        'tasks' => __('Tasks'),
        'vendors' => __('Vendors'),
    ];

    $eventsOpen = request()->routeIs('events.*', 'events.guests.*', 'check-ins.*');
@endphp

<ul class="menubar" data-emca-sync="menu">
    @if (auth()->user()->isSuperAdmin())
        @php
            $adminMenu = [
                __('Administration') => [
                    ['label' => __('Overview'), 'icon' => 'fi-rr-apps', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
                    ['label' => __('Search'), 'icon' => 'fi-rr-search', 'route' => 'admin.search', 'active' => 'admin.search'],
                ],
                __('Customers') => [
                    ['label' => __('Hosts'), 'icon' => 'fi-rr-users', 'route' => 'admin.users.index', 'active' => ['admin.users.index', 'admin.users.show', 'admin.users.edit']],
                    ['label' => __('Onboard a host'), 'icon' => 'fi-rr-user-add', 'modal' => '#onboardHostModal'],
                    ['label' => __('Demo requests'), 'icon' => 'fi-rr-comment', 'route' => 'demos.index', 'active' => 'demos.index', 'badge' => $adminNewDemos ?? 0],
                ],
                __('Events') => [
                    ['label' => __('Events'), 'icon' => 'fi-rr-calendar', 'open' => 'admin.events.*', 'children' => [
                        ['label' => __('All events'), 'route' => 'admin.events.index', 'active' => ['admin.events.index', 'admin.events.show'], 'exact' => ['when' => null]],
                        ['label' => __('Upcoming'), 'route' => 'admin.events.index', 'params' => ['when' => 'upcoming'], 'exact' => ['when' => 'upcoming']],
                        ['label' => __('Past'), 'route' => 'admin.events.index', 'params' => ['when' => 'past'], 'exact' => ['when' => 'past']],
                    ]],
                    ['label' => __('Message log'), 'icon' => 'fi-rr-paper-plane', 'route' => 'admin.messages.index', 'active' => 'admin.messages.index'],
                ],
                __('Finance') => [
                    ['label' => __('Top-ups'), 'icon' => 'fi-rr-wallet', 'route' => 'admin.top-ups.index', 'active' => 'admin.top-ups.index', 'badge' => $adminPendingTopUpCount ?? 0],
                    ['label' => __('Subscriptions'), 'icon' => 'fi-rr-calendar-clock', 'route' => 'admin.subscriptions.index', 'active' => 'admin.subscriptions.index'],
                    ['label' => __('Transactions'), 'icon' => 'fi-rr-receipt', 'open' => 'admin.transactions.*', 'children' => [
                        ['label' => __('All transactions'), 'route' => 'admin.transactions.index', 'exact' => ['type' => null]],
                        ['label' => __('Subscription payments'), 'route' => 'admin.transactions.index', 'params' => ['type' => 'subscription'], 'exact' => ['type' => 'subscription']],
                        ['label' => __('Package sales'), 'route' => 'admin.transactions.index', 'params' => ['type' => 'package_purchase'], 'exact' => ['type' => 'package_purchase']],
                        ['label' => __('Message charges'), 'route' => 'admin.transactions.index', 'params' => ['type' => 'message_charge'], 'exact' => ['type' => 'message_charge']],
                    ]],
                ],
                __('Catalog') => [
                    ['label' => __('Packages'), 'icon' => 'fi-rr-box-open', 'route' => 'admin.packages.index', 'active' => 'admin.packages.index'],
                    ['label' => __('Pricing'), 'icon' => 'fi-rr-tags', 'route' => 'admin.pricing.edit', 'active' => 'admin.pricing.edit'],
                    ['label' => __('Card templates'), 'icon' => 'fi-rr-picture', 'route' => 'admin.templates.index', 'active' => 'admin.templates.index'],
                    ['label' => __('Event categories'), 'icon' => 'fi-rr-apps', 'route' => 'admin.event-categories.index', 'active' => 'admin.event-categories.*'],
                ],
                __('System') => [
                    ['label' => __('SMS & security'), 'icon' => 'fi-rr-settings', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*'],
                ],
                __('Public website') => [
                    ['label' => __('Home'), 'icon' => 'fi-rr-home', 'route' => 'home', 'external' => true],
                    ['label' => __('Pricing page'), 'icon' => 'fi-rr-dollar', 'route' => 'pricing', 'external' => true],
                    ['label' => __('Templates page'), 'icon' => 'fi-rr-picture', 'route' => 'templates.index', 'external' => true],
                    ['label' => __('Upcoming events'), 'icon' => 'fi-rr-calendar-star', 'route' => 'events.upcoming', 'external' => true],
                ],
            ];

            $isActiveAdminItem = function (array $item): bool {
                if (isset($item['exact'])) {
                    if (! request()->routeIs($item['route'])) {
                        return false;
                    }

                    foreach ($item['exact'] as $key => $value) {
                        if (request()->query($key) !== $value) {
                            return false;
                        }
                    }

                    return true;
                }

                return isset($item['active']) && request()->routeIs(...(array) $item['active']);
            };
        @endphp

        @foreach ($adminMenu as $heading => $items)
            <li class="menu-heading">
                <span class="menu-label">{{ $heading }}</span>
            </li>
            @foreach ($items as $item)
                @if (isset($item['children']))
                    @php($isOpen = request()->routeIs($item['open']))
                    <li @class(['menu-item', 'menu-arrow', 'open' => $isOpen])>
                        <a @class(['menu-link', 'active open' => $isOpen]) href="javascript:void(0);" role="button">
                            <i class="fi {{ $item['icon'] }}"></i>
                            <span class="menu-label">{{ $item['label'] }}</span>
                        </a>
                        <ul class="menu-inner" @if ($isOpen) style="display: block;" @endif>
                            @foreach ($item['children'] as $child)
                                <li class="menu-item">
                                    <a @class(['menu-link', 'active' => $isActiveAdminItem($child)]) href="{{ route($child['route'], $child['params'] ?? []) }}">
                                        <span class="menu-label">{{ $child['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @elseif (isset($item['modal']))
                    <li class="menu-item">
                        <button class="menu-link w-100 border-0 text-start bg-transparent" type="button" data-bs-toggle="modal" data-bs-target="{{ $item['modal'] }}">
                            <i class="fi {{ $item['icon'] }}"></i>
                            <span class="menu-label">{{ $item['label'] }}</span>
                        </button>
                    </li>
                @else
                    <li class="menu-item">
                        <a @class(['menu-link', 'active' => $isActiveAdminItem($item)]) href="{{ route($item['route'], $item['params'] ?? []) }}" @if (! empty($item['external'])) target="_blank" rel="noopener" @endif>
                            <i class="fi {{ $item['icon'] }}"></i>
                            <span class="menu-label">{{ $item['label'] }}</span>
                            @if (! empty($item['badge']))
                                <span class="badge bg-primary-subtle text-primary ms-auto">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                            @endif
                        </a>
                    </li>
                @endif
            @endforeach
        @endforeach
    @else
        <li class="menu-heading">
            <span class="menu-label">{{ __('Menu') }}</span>
        </li>
        <li class="menu-item">
            <a @class(['menu-link', 'active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">
                <i class="fi fi-rr-apps"></i>
                <span class="menu-label">{{ __('Overview') }}</span>
            </a>
        </li>
        <li @class(['menu-item', 'menu-arrow', 'open' => $eventsOpen])>
            <a @class(['menu-link', 'active open' => $eventsOpen]) href="javascript:void(0);" role="button">
                <i class="fi fi-rr-calendar"></i>
                <span class="menu-label">{{ __('Events') }}</span>
            </a>
            <ul class="menu-inner" @if ($eventsOpen) style="display: block;" @endif>
                <li class="menu-item">
                    <a @class(['menu-link', 'active' => request()->routeIs('events.index')]) href="{{ route('events.index') }}">
                        <span class="menu-label">{{ __('All events') }}</span>
                    </a>
                </li>
                <li class="menu-item">
                    <x-new-event variant="menu">
                        <span class="menu-label">{{ __('New event') }}</span>
                    </x-new-event>
                </li>
                @if ($menuEvent)
                    <li class="menu-item">
                        <a @class(['menu-link', 'active' => request()->routeIs('events.show')]) href="{{ route('events.show', $menuEvent) }}">
                            <span class="menu-label">{{ $menuEvent->title }}</span>
                        </a>
                    </li>
                    @foreach ($eventSections as $id => $label)
                        <li class="menu-item">
                            <a class="menu-link" href="{{ route('events.show', $menuEvent) }}#{{ $id }}">
                                <span class="menu-label">{{ $label }}</span>
                            </a>
                        </li>
                    @endforeach
                    <li class="menu-item">
                        <a @class(['menu-link', 'active' => request()->routeIs('events.design.edit')]) href="{{ route('events.design.edit', $menuEvent) }}">
                            <span class="menu-label">{{ __('Design the card') }}</span>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a @class(['menu-link', 'active' => request()->routeIs('events.edit')]) href="{{ route('events.edit', $menuEvent) }}">
                            <span class="menu-label">{{ __('Edit event') }}</span>
                        </a>
                    </li>
                @endif
            </ul>
        </li>
        <li class="menu-item">
            <a @class(['menu-link', 'active' => request()->routeIs('template-library.index')]) href="{{ route('template-library.index') }}">
                <i class="fi fi-rr-picture"></i>
                <span class="menu-label">{{ __('Templates') }}</span>
            </a>
        </li>
        <li class="menu-heading">
            <span class="menu-label">{{ __('Money') }}</span>
        </li>
        @php($hostHasSubscription = auth()->user()->hasActiveSubscription())
        <li class="menu-item">
            <a @class(['menu-link', 'active' => request()->routeIs('subscription.*')]) href="{{ route('subscription.index') }}">
                <i class="fi fi-rr-calendar-clock"></i>
                <span class="menu-label">{{ __('Subscription') }}</span>
                @if ($hostHasSubscription)
                    <span class="badge bg-success-subtle text-success ms-auto">{{ trans_choice(':count day|:count days', auth()->user()->subscriptionDaysLeft(), ['count' => auth()->user()->subscriptionDaysLeft()]) }}</span>
                @elseif (\App\Support\SystemSettings::subscriptionRequired())
                    <span class="badge bg-warning-subtle text-warning ms-auto">{{ __('Inactive') }}</span>
                @endif
            </a>
        </li>
        <li class="menu-item">
            <a @class(['menu-link', 'active' => request()->routeIs('wallet.*')]) href="{{ route('wallet.index') }}">
                <i class="fi fi-rr-wallet"></i>
                <span class="menu-label">{{ __('Wallet') }}</span>
                <span class="badge bg-primary-subtle text-primary ms-auto">{{ number_format(auth()->user()->wallet_balance) }}</span>
            </a>
        </li>
        <li class="menu-item">
            <a @class(['menu-link', 'active' => request()->routeIs('packages.*')]) href="{{ route('packages.index') }}">
                <i class="fi fi-rr-box-open"></i>
                <span class="menu-label">{{ __('Packages') }}</span>
            </a>
        </li>
        <li class="menu-item">
            <a @class(['menu-link', 'active' => request()->routeIs('profile.edit')]) href="{{ route('profile.edit') }}">
                <i class="fi fi-rr-user"></i>
                <span class="menu-label">{{ __('Profile details') }}</span>
            </a>
        </li>
    @endif
</ul>
