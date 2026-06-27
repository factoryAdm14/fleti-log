<div class="fleti-metric-card {{ $class ?? '' }}">
    @if(!empty($icon))
        <div class="fleti-metric-card__icon">
            <i class="{{ $icon }}"></i>
        </div>
    @endif
    <div>
        @if(!empty($label))
            <div class="fleti-metric-card__label">{{ $label }}</div>
        @endif
        <div class="fleti-metric-card__value">{{ $value ?? '0' }}</div>
        @if(!empty($subtitle))
            <div class="fleti-metric-card__label mt-1">{{ $subtitle }}</div>
        @endif
    </div>
</div>
