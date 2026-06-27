<div class="fleti-chart-card {{ $class ?? '' }}">
    @if(!empty($title) || !empty($actions))
        <div class="fleti-chart-card__header">
            @if(!empty($title))
                <h6 class="fleti-chart-card__title">{{ $title }}</h6>
            @endif
            @if(!empty($actions))
                <div class="fleti-chart-card__actions">{{ $actions }}</div>
            @endif
        </div>
    @endif
    <div class="fleti-chart-card__body">
        {!! $body ?? '' !!}
    </div>
</div>
