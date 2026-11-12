@props(['icon', 'value', 'label', 'trend' => null, 'tone' => 'primary', 'prefix' => null, 'note' => null])

<div {{ $attributes->merge(['class' => 'card emca-stat-tile emca-stat-tile-'.$tone.' shadow-none border-0']) }}>
    <div class="card-body">
        <div class="avatar emca-stat-tile-icon rounded-circle text-white mb-3">
            <i class="fi {{ $icon }}"></i>
        </div>
        <h3 class="mb-1">
            @if ($prefix)
                <small class="text-body fs-6 fw-semibold">{{ $prefix }}</small>
            @endif
            {{ $value }}
        </h3>
        <h6 class="mb-2">{{ $label }}</h6>
        <small class="fw-medium text-body emca-stat-trend">
            @if (! is_null($trend))
                <span @class(['fw-semibold', 'text-primary' => $trend >= 0, 'text-dark' => $trend < 0])>
                    <i @class(['fi scale-3x', 'fi-rr-arrow-small-up' => $trend >= 0, 'fi-rr-arrow-small-down' => $trend < 0])></i>
                    {{ $trend > 0 ? '+' : '' }}{{ $trend }}%
                </span>
                {{ __('vs last month') }}
            @else
                {{ $note }}
            @endif
        </small>
    </div>
</div>
