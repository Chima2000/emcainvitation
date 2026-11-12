<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-admin-theme="emca" data-color-theme="emca">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#940000">
        <title>@yield('title', __('Dashboard')) | {{ config('app.name') }}</title>
        @include('layouts.partials.admin-head')
        <link rel="stylesheet" href="{{ asset('admin/assets/css/emca-admin.css') }}?v={{ filemtime(public_path('admin/assets/css/emca-admin.css')) }}">
        <link rel="stylesheet" href="{{ asset('admin/assets/css/emca-ajax.css') }}?v={{ filemtime(public_path('admin/assets/css/emca-ajax.css')) }}">
        @stack('styles')
    </head>
    <body @class(['profile-edit-page' => request()->routeIs('profile.edit')]) data-emca-layout="dashboard{{ View::hasSection('sidebar-panel') ? '-panel' : '' }}" data-emca-swap="all">
        <div class="page-layout">
            <header class="app-header">
                <div class="app-header-inner">
                    <button class="app-toggler" type="button" aria-label="{{ __('Menu') }}">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <div class="app-header-start d-none d-md-flex">
                        <ul class="navbar-nav gap-4 flex-row">
                            @if (auth()->user()->isSuperAdmin())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.dashboard') }}">{{ __('Overview') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.users.index') }}">{{ __('Hosts') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('demos.index') }}">{{ __('Demo requests') }}</a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('dashboard') }}">{{ __('Overview') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('events.index') }}">{{ __('Events') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('template-library.index') }}">{{ __('Templates') }}</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                    <div class="app-header-end">
                        <div class="px-lg-3 px-2 ps-0 d-flex align-items-center">
                            <x-locale-switcher tone="dashboard" />
                        </div>
                        <div class="px-lg-3 px-2 ps-0 d-flex align-items-center">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-action-gray rounded-circle waves-effect waves-light position-relative" id="ld-theme" type="button" data-bs-auto-close="outside" aria-expanded="false" data-bs-toggle="dropdown">
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
                        @unless (auth()->user()->isSuperAdmin())
                            <div class="px-lg-2 px-1 d-flex align-items-center" data-emca-sync="wallet-chip">
                                <a href="{{ route('wallet.index') }}" class="btn btn-sm btn-outline-light btn-shadow d-inline-flex align-items-center gap-2 emca-wallet-chip" aria-label="{{ __('Wallet balance') }}">
                                    <i class="fi fi-rr-wallet text-primary"></i>
                                    <span class="fw-semibold text-dark text-nowrap">TZS {{ number_format(auth()->user()->wallet_balance) }}</span>
                                </a>
                            </div>
                        @endunless
                        <div class="vr my-3"></div>
                        @hasSection('sidebar-panel')
                            <button type="button" class="btn btn-primary ms-2 btn-icon btn-sm sidebar-panel-toggler" aria-label="{{ __('Details') }}">
                                <i class="fi fi-rr-bars-sort"></i>
                            </button>
                        @endif
                        <div class="dropdown text-end ms-sm-3 ms-2 ms-lg-4">
                            <a href="#" class="d-flex align-items-center py-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <div class="text-end me-2 d-none d-lg-inline-block">
                                    <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                                    <small class="text-body d-block lh-sm">
                                        <i class="fi fi-rr-angle-down text-3xs me-1"></i> {{ auth()->user()->role->label() }}
                                    </small>
                                </div>
                                <div class="avatar avatar-sm rounded-circle bg-primary text-white">
                                    {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end w-225px mt-1">
                                <li class="d-flex align-items-center p-2">
                                    <div class="avatar avatar-sm rounded-circle bg-primary text-white">
                                        {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                                    </div>
                                    <div class="ms-2">
                                        <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                                        <small class="text-body d-block lh-sm">{{ auth()->user()->localPhone() }}</small>
                                    </div>
                                </li>
                                <li>
                                    <div class="dropdown-divider my-1"></div>
                                </li>
                                @if (auth()->user()->isSuperAdmin())
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
                                @else
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                                            <i class="fi fi-rr-apps scale-1x"></i> {{ __('Overview') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('profile.edit') }}">
                                            <i class="fi fi-rr-user scale-1x"></i> {{ __('Profile details') }}
                                        </a>
                                    </li>
                                    <li>
                                        <x-new-event variant="dropdown">
                                            <i class="fi fi-rr-plus scale-1x"></i> {{ __('New event') }}
                                        </x-new-event>
                                    </li>
                                @endif
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
                    <a class="navbar-brand-logo emca-brand" href="{{ auth()->user()->isSuperAdmin() ? route('admin.dashboard') : route('dashboard') }}" aria-label="Jumuika Pass">
                        <img src="{{ asset('logo/jumuika-pass.png') }}" alt="Jumuika Pass" width="757" height="389">
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
                    <div @class([
                        'app-page-head d-flex flex-wrap gap-3 align-items-center',
                        'justify-content-center text-center' => request()->routeIs('profile.edit'),
                        'justify-content-between' => ! request()->routeIs('profile.edit'),
                    ])>
                        <div class="clearfix">
                            <h1 class="app-page-title">@yield('heading', __('Dashboard'))</h1>
                            @hasSection('breadcrumb')
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0">
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
                <div class="container-fluid emca-admin-container">
                    <p class="mb-0 text-center text-md-start">© <span class="currentYear">{{ now()->year }}</span> {{ config('app.name') }}.</p>
                </div>
            </footer>
        </div>
        <div data-emca-region="modals">
            @include('layouts.partials.logout-modal')
            @if (auth()->check() && ! auth()->user()->isSuperAdmin())
                @include('events.partials.create-modal')
            @else
                @include('admin.users.create-modal')
            @endif
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
