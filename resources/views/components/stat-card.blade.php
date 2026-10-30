@props(['label', 'value', 'icon', 'change' => null, 'hint' => null, 'tone' => null])
<div {{ $attributes->class('card stat-card h-100') }}>
    <div class="card-body d-flex gap-3 align-items-start">
        <div class="stat-icon" aria-hidden="true"><i class="bi bi-{{ $icon }}"></i></div>
        <div class="min-w-0">
            <div class="stat-label">{{ $label }}</div>
            <div @class(['stat-value', "text-{$tone}" => $tone])>{{ $value }}</div>
            @if (! is_null($change))
                <div class="small {{ $change >= 0 ? 'text-success' : 'text-danger' }}">
                    <i class="bi bi-arrow-{{ $change >= 0 ? 'up' : 'down' }}-right" aria-hidden="true"></i>
                    {{ $change >= 0 ? '+' : '' }}{{ $change }}% <span class="text-secondary">vs previous period</span>
                </div>
            @elseif ($hint)
                <div class="small text-secondary">{{ $hint }}</div>
            @endif
        </div>
    </div>
</div>
