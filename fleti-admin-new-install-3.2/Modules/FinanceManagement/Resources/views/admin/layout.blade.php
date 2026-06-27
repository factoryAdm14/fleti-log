@extends('adminmodule::layouts.master')

@push('css_or_js')
    @include('financemanagement::admin.partials._styles')
@endpush

@section('content')
    <div class="main-content finance-ui">
        <div class="container-fluid">
            @include('financemanagement::admin.partials._subnav')
            @yield('finance_content')
        </div>
    </div>
@endsection
