@php
    $tone = $tone ?? 'neutral';
    $money = $money ?? true;
@endphp
<div class="fin-stat tone-{{ $tone }}">
    <div class="fin-stat-label">{{ $label }}</div>
    <div class="fin-stat-value">
        @if($money)
            {{ set_currency_symbol($value) }}
        @else
            {{ is_numeric($value) ? number_format($value) : $value }}
        @endif
    </div>
    @if(!empty($suffix))
        <div class="fin-stat-suffix">{{ $suffix }}</div>
    @endif
</div>
