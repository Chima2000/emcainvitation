@extends('layouts.admin')

@section('title', __('Hosts'))
@section('heading', __('Host accounts'))

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">{{ __('Hosts') }}</li>
@endsection

@section('actions')
    <x-button type="button" data-bs-toggle="modal" data-bs-target="#onboardHostModal">
        <i class="fi fi-rr-plus me-1"></i> {{ __('Onboard a host') }}
    </x-button>
@endsection

@section('content')
    <div class="row">
        @foreach ([
            ['label' => __('Hosts'), 'value' => number_format($totals['hosts']), 'icon' => 'fi-rr-users-alt', 'tone' => ''],
            ['label' => __('Hosts with balance'), 'value' => number_format($totals['with_balance']), 'icon' => 'fi-rr-wallet', 'tone' => 'is-dark'],
            ['label' => __('Money in wallets'), 'value' => 'TZS '.number_format($totals['wallet_float']), 'icon' => 'fi-rr-coins', 'tone' => 'is-soft'],
            ['label' => __('Profiles to finish'), 'value' => number_format($totals['pending_profiles']), 'icon' => 'fi-rr-time-past', 'tone' => 'is-soft'],
        ] as $kpi)
            <div class="col-xl-3 col-sm-6">
                <div class="card emca-kpi">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="emca-kpi-icon {{ $kpi['tone'] }}"><i class="fi {{ $kpi['icon'] }}"></i></span>
                        <div class="min-w-0">
                            <span class="d-block text-body text-sm">{{ $kpi['label'] }}</span>
                            <strong class="d-block fs-5 text-dark text-truncate">{{ $kpi['value'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="emca-filter-pills">
                <a href="{{ route('admin.users.index', request()->except(['role', 'page'])) }}" @class(['active' => ! $role])>{{ __('Everyone') }}</a>
                @foreach (\App\UserRole::cases() as $roleOption)
                    <a href="{{ route('admin.users.index', [...request()->except('page'), 'role' => $roleOption->value]) }}" @class(['active' => $role === $roleOption])>{{ $roleOption->label() }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex gap-2" role="search">
                @if ($role)
                    <input type="hidden" name="role" value="{{ $role->value }}">
                @endif
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="{{ __('Name, phone, email or region') }}" aria-label="{{ __('Search hosts') }}">
                <button class="btn btn-sm btn-primary" type="submit"><i class="fi fi-rr-search"></i></button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Sign-in number') }}</th>
                        <th>{{ __('Location / profile') }}</th>
                        <th class="text-center">{{ __('Events') }}</th>
                        <th class="text-end">{{ __('Wallet') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $account)
                        <tr>
                            <td>
                                <a href="{{ route('admin.users.show', $account) }}" class="d-flex align-items-center gap-2 text-dark">
                                    <span class="avatar avatar-xs rounded-circle bg-primary-subtle text-primary flex-shrink-0">{{ mb_strtoupper(mb_substr($account->name, 0, 1)) }}</span>
                                    <span class="min-w-0">
                                        <span class="d-block fw-semibold text-truncate">{{ $account->name }}</span>
                                        <small class="d-block text-body text-truncate">{{ $account->email }}</small>
                                    </span>
                                </a>
                            </td>
                            <td class="text-nowrap">
                                {{ $account->localPhone() }}
                                <small class="d-block text-body">{{ $account->phone }}</small>
                            </td>
                            <td>
                                @php
                                    $accountLocation = collect([$account->region, $account->district, $account->ward])
                                        ->filter()
                                        ->implode(', ');
                                @endphp
                                @if ($account->hasCompletedProfile())
                                    {{ $accountLocation ?: __('Address not provided') }}
                                @else
                                    <span class="badge bg-warning-subtle text-dark">{{ __('Profile details pending') }}</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $account->events_count }}</td>
                            <td class="text-end text-nowrap fw-semibold">
                                @if ($account->isSuperAdmin())
                                    <span class="text-body">—</span>
                                @else
                                    TZS {{ number_format($account->wallet_balance) }}
                                @endif
                            </td>
                            <td><span @class(['badge', 'bg-dark' => $account->isSuperAdmin(), 'bg-light text-dark' => ! $account->isSuperAdmin()])>{{ $account->role->label() }}</span></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.users.show', $account) }}" class="btn btn-sm btn-icon btn-light" data-bs-toggle="tooltip" data-bs-title="{{ __('Open') }}" aria-label="{{ __('Open') }}"><i class="fi fi-rr-eye"></i></a>
                                    @can('update', $account)
                                        <a href="{{ route('admin.users.edit', $account) }}" class="btn btn-sm btn-icon btn-light" data-bs-toggle="tooltip" data-bs-title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}"><i class="fi fi-rr-edit"></i></a>
                                    @endcan
                                    @can('delete', $account)
                                        <form method="POST" action="{{ route('admin.users.destroy', $account) }}" data-emca-confirm="{{ __('Remove :name and all of their events? This cannot be undone.', ['name' => $account->name]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-icon btn-light text-danger" type="submit" data-bs-toggle="tooltip" data-bs-title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}"><i class="fi fi-rr-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="emca-empty">{{ request('q') ? __('No host matches that search.') : __('No hosts yet. Onboard the first account.') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
@endsection
