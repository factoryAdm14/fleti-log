@php
    $type = $type ?? 'neutral';
@endphp
<span class="fleti-status-badge fleti-status-badge--{{ $type }} {{ $class ?? '' }}">
    {{ $label }}
</span>
