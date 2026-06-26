@php
    $variant = $variant ?? 'primary';
    $tag = $tag ?? 'button';
    $classes = 'fleti-btn fleti-btn--' . $variant . ' ' . ($class ?? '');
@endphp

@if($tag === 'a')
    <a href="{{ $href ?? '#' }}" class="{{ $classes }}" @if(!empty($id)) id="{{ $id }}" @endif>
        @if(!empty($icon)) <i class="{{ $icon }}"></i> @endif
        {{ $label }}
    </a>
@else
    <button type="{{ $type ?? 'button' }}" class="{{ $classes }}" @if(!empty($id)) id="{{ $id }}" @endif @if(!empty($disabled)) disabled @endif>
        @if(!empty($icon)) <i class="{{ $icon }}"></i> @endif
        {{ $label }}
    </button>
@endif
