<div class="fin-page-head">
    <div>
        <h2>{{ $title }}</h2>
        @if(!empty($subtitle))
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    @if(!empty($actions))
        <div class="fin-actions">
            {!! $actions !!}
        </div>
    @endif
</div>
