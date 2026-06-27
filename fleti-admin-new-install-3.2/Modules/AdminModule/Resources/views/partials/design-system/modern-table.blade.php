<div class="table-responsive">
    <table class="fleti-table {{ $class ?? '' }}">
        @if(!empty($head))
            <thead>
                <tr>
                    @foreach($head as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {!! $body ?? '' !!}
        </tbody>
    </table>
</div>
