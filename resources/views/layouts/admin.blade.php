<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-admin-theme="emca" data-color-theme="emca">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#940000">
        <meta name="robots" content="noindex, nofollow">
        <title>@yield('title', __('Dashboard')) | {{ config('app.name') }}</title>
        @include('layouts.partials.admin-head')
        <link rel="stylesheet" href="{{ asset('admin/assets/css/emca-admin.css') }}?v={{ filemtime(public_path('admin/assets/css/emca-admin.css')) }}">
        <link rel="stylesheet" href="{{ asset('admin/assets/css/emca-ajax.css') }}?v={{ filemtime(public_path('admin/assets/css/emca-ajax.css')) }}">
        @stack('styles')
    </head>
    <body class="emca-admin-shell" data-emca-layout="admin{{ View::hasSection('sidebar-panel') ? '-panel' : '' }}" data-emca-swap="all">
        @php
            $adminUser = auth()->user();
        @endphp

        <div class="page-layout">
            <header class="app-header">
                <div class="app-header-inner">
                    <button class="app-toggler" type="button" aria-label="{{ __('Menu') }}">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <div class="app-header-start d-none d-md-flex ms-2">
                        <form class="d-flex align-items-center h-100 w-lg-250px w-xxl-300px position-relative" method="GET" action="{{ route('admin.search') }}" role="search">
                            <button type="submit" class="btn btn-sm border-0 position-absolute start-0 ms-3 p-0" aria-label="{{ __('Search') }}">
                                <i class="fi fi-rr-search"></i>
                            </button>
                            <input type="search" name="q" value="{{ request()->routeIs('admin.search') ? request('q') : '' }}" class="form-control rounded-5 ps-5" placeholder="{{ __('Search hosts, events, references') }}" aria-label="{{ __('Search') }}">
                        </form>
                    </div>
                    <div class="app-header-end">
                        <div class="px-lg-3 px-2 ps-0 d-flex align-items-center">
                            <x-locale-switcher tone="dashboard" />
                        </div>
                        <div class="px-lg-2 px-1 d-flex align-items-center">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-white waves-effect waves-light position-relative" id="ld-theme" type="button" data-bs-auto-close="outside" aria-expanded="false" data-bs-toggle="dropdown" aria-label="{{ __('Theme') }}">
                                    <i class="fi fi-rr-brightness scale-1x theme-icon-active"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item d-flex gap-2 align-items-center active" data-bs-theme-value="light" aria-pressed="true">
                                            <i class="fi fi-rr-brightness scale-1x" data-theme="light"></i> {{ __('Light') }}
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                                            <i class="fi fi-rr-moon scale-1x" data-theme="dark"></i> {{ __('Dark') }}
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="auto" aria-pressed="false">
                                            <i class="fi fi-br-circle-half-stroke scale-1x" data-theme="auto"></i> {{ __('Auto') }}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="vr my-3"></div>
                        <div class="d-flex align-items-center gap-2 px-lg-3 px-2">
                            <a href="{{ route('demos.index') }}" class="btn btn-icon btn-white waves-effect waves-light position-relative" aria-label="{{ __('Demo requests') }}" data-emca-sync="demo-inbox">
                                <i class="fi fi-rr-envelope"></i>
                                @if ($adminNewDemos > 0)
                                    <span class="position-absolute top-0 end-0 p-1 mt-1 me-1 bg-primary border border-3 border-light rounded-circle">
                                        <span class="visually-hidden">{{ __('New demo requests') }}</span>
                                    </span>
                                @endif
                            </a>
                            <div class="dropdown text-end" data-emca-sync="top-up-alerts">
                                <button type="button" class="btn btn-icon btn-white waves-effect waves-light position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="{{ __('Notifications') }}">
                                    <i class="fi fi-rr-bell"></i>
                                    @if ($adminPendingTopUpCount > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary emca-header-badge">{{ $adminPendingTopUpCount > 99 ? '99+' : $adminPendingTopUpCount }}</span>
                                    @endif
                                </button>
                                <div class="dropdown-menu dropdown-menu-lg-end p-0 w-300px mt-2">
                                    <div class="px-3 py-3 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">{{ __('Top-ups to confirm') }} <span class="badge badge-sm rounded-pill bg-primary ms-2">{{ $adminPendingTopUpCount }}</span></h6>
                                    </div>
                                    <div class="emca-dropdown-scroll" data-simplebar>
                                        <ul class="list-unstyled mb-0">
                                            @forelse ($adminPendingTopUps as $pendingTopUp)
                                                <li>
                                                    <a href="{{ route('admin.top-ups.index', ['status' => 'pending']) }}" class="d-flex align-items-start gap-2 px-3 py-2 text-start text-dark emca-dropdown-row">
                                                        <span class="avatar avatar-sm rounded-circle bg-primary-subtle text-primary flex-shrink-0">
                                                            <i class="fi fi-rr-wallet"></i>
                                                        </span>
                                                        <span class="min-w-0">
                                                            <span class="d-block fw-semibold text-truncate">{{ $pendingTopUp->user?->name }}</span>
                                                            <small class="d-block text-body">TZS {{ number_format($pendingTopUp->amount) }} · {{ $pendingTopUp->method->label() }}</small>
                                                            <small class="d-block text-body">{{ $pendingTopUp->created_at->diffForHumans() }}</small>
                                                        </span>
                                                    </a>
                                                </li>
                                            @empty
                                                <li class="px-3 py-4 text-center text-body">{{ __('Nothing waiting. Every top-up is confirmed.') }}</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                    <div class="p-2 border-top">
                                        <a href="{{ route('admin.top-ups.index', ['status' => 'pending']) }}" class="btn w-100 btn-primary waves-effect waves-light">{{ __('Review top-ups') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vr my-3"></div>
                        @hasSection('sidebar-panel')
                            <button type="button" class="btn btn-primary ms-2 btn-icon btn-sm sidebar-panel-toggler" aria-label="{{ __('Summary') }}">
                                <i class="fi fi-rr-bars-sort"></i>
                            </button>
                        @endif
                        <div class="dropdown text-end ms-sm-3 ms-2 ms-lg-4">
                            <a href="#" class="d-flex align-items-center py-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <div class="text-end me-2 d-none d-lg-inline-block">
                                    <div class="fw-bold text-dark">{{ $adminUser->name }}</div>
                                    <small class="text-body d-block lh-sm">
                                        <i class="fi fi-rr-angle-down text-3xs me-1"></i> {{ $adminUser->role->label() }}
                                    </small>
                                </div>
                                <div class="avatar avatar-sm rounded-circle bg-primary text-white">
                                    {{ mb_strtoupper(mb_substr($adminUser->name, 0, 1)) }}
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end w-225px mt-1">
                                <li class="d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm rounded-circle bg-primary text-white">
                                        {{ mb_strtoupper(mb_substr($adminUser->name, 0, 1)) }}
                                    </div>
                                    <div class="ms-2 min-w-0">
                                        <div class="fw-bold text-dark text-truncate">{{ $adminUser->name }}</div>
                                        <small class="text-body d-block lh-sm">{{ $adminUser->localPhone() }}</small>
                                    </div>
                                </li>
                                <li>
                                    <div class="dropdown-divider my-1"></div>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
                                        <i class="fi fi-rr-apps scale-1x"></i> {{ __('Overview') }}
                                    </a>
                                </li>
                                <li>
                                    <button class="dropdown-item d-flex align-items-center gap-2" type="button" data-bs-toggle="modal" data-bs-target="#onboardHostModal">
                                        <i class="fi fi-rr-user-add scale-1x"></i> {{ __('Onboard a host') }}
                                    </button>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('admin.top-ups.index', ['status' => 'pending']) }}">
                                        <i class="fi fi-rr-wallet scale-1x"></i> {{ __('Top-ups') }}
                                    </a>
                                </li>
                                <li>
                                    <div class="dropdown-divider my-1"></div>
                                </li>
                                <li>
                                    <button class="dropdown-item d-flex align-items-center gap-2 text-danger" type="button" data-bs-toggle="modal" data-bs-target="#logoutConfirmationModal">
                                        <i class="fi fi-sr-exit scale-1x"></i> {{ __('Sign out') }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <aside class="app-menubar" id="appMenubar">
                <div class="app-navbar-brand">
                    <a class="navbar-brand-logo emca-brand" href="{{ route('admin.dashboard') }}" aria-label="{{ config('app.name') }}">
                        <img src="{{ asset('logo/jumuika-pass.png') }}" alt="{{ config('app.name') }}" width="757" height="389">
                    </a>
                </div>
                <nav class="app-navbar" data-simplebar>
                    @include('layouts.partials.admin-menu')
                </nav>
            </aside>

            @hasSection('sidebar-panel')
                <div class="app-sidebar-panel" data-simplebar>
                    <button type="button" class="btn btn-light btn-sm btn-icon sidebar-close rounded-0" aria-label="{{ __('Close') }}">
                        <i class="fi fi-rr-cross-small"></i>
                    </button>
                    @yield('sidebar-panel')
                </div>
            @endif

            <div class="emca-menubar-backdrop d-xl-none" data-emca-menubar-close></div>

            <main class="app-wrapper" data-emca-region="page">
                <div class="container-fluid emca-admin-container">
                    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
                        <div class="clearfix min-w-0">
                            <h1 class="app-page-title">@yield('heading', __('Dashboard'))</h1>
                            @hasSection('breadcrumb')
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0">
                                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                                        @yield('breadcrumb')
                                    </ol>
                                </nav>
                            @elseif (View::hasSection('subheading'))
                                <span>@yield('subheading')</span>
                            @endif
                        </div>
                        @hasSection('actions')
                            <div class="d-flex flex-wrap gap-2">
                                @yield('actions')
                            </div>
                        @endif
                    </div>
                    <x-flash />
                    @yield('content')
                </div>
            </main>

            <footer class="footer-wrapper bg-body">
                <div class="container-fluid">
                    <p class="mb-0 text-center text-md-start">© <span class="currentYear">{{ now()->year }}</span> {{ config('app.name') }}. {{ __('Invite better. Manage better. Spend less.') }}</p>
                </div>
            </footer>
        </div>

        <div data-emca-region="modals">
            @include('layouts.partials.logout-modal')
            @include('admin.users.create-modal')
            @stack('modals')
        </div>
        @include('layouts.partials.admin-scripts')
        <script src="{{ asset('admin/assets/js/emca-admin.js') }}?v={{ filemtime(public_path('admin/assets/js/emca-admin.js')) }}"></script>
        @include('layouts.partials.ajax-script')
        <div data-emca-region="scripts">
            @stack('scripts')
        </div>
    </body>
</html>
