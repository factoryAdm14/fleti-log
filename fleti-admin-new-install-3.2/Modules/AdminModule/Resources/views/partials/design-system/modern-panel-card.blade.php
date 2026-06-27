<div class="fleti-panel-card {{ $class ?? '' }}">
    @if(!empty($title))
        <div class="fleti-panel-card__title">{{ $title }}</div>
    @endif
    <div class="fleti-panel-card__body">
        {!! $body ?? '' !!}
    </div>
</div>
