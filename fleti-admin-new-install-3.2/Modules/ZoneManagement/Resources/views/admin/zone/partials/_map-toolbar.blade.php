@php
    $canDrawZone = auth()->user()->user_type === 'super-admin'
        || \Illuminate\Support\Facades\Gate::check('zone_add')
        || \Illuminate\Support\Facades\Gate::check('zone_edit');
@endphp
<div class="fleti-zone-map-toolbar d-flex flex-wrap align-items-center gap-2 mb-2 p-2 rounded border bg-white"
     id="fleti-zone-map-toolbar">
    <button type="button" class="btn btn-primary btn-sm" id="fleti-zone-start-draw"
            @unless($canDrawZone) disabled @endunless>
        <i class="bi bi-pentagon"></i> {{ translate('start_drawing_zone') }}
    </button>
    <button type="button" class="btn btn-success btn-sm" id="fleti-zone-finish-draw"
            @unless($canDrawZone) disabled @endunless>
        <i class="bi bi-check-lg"></i> {{ translate('finish_zone') }}
    </button>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="fleti-zone-clear-draw"
            @unless($canDrawZone) disabled @endunless>
        <i class="bi bi-eraser"></i> {{ translate('clear_drawing') }}
    </button>
    <span class="small text-muted ms-1" id="fleti-zone-draw-status">
        {{ translate('zone_draw_hint_idle') }}
    </span>
</div>
