@extends('adminmodule::layouts.master')

@section('title', translate('pix_transactions_report'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <h4 class="text-capitalize mb-3">{{ translate('Report Analytics') }}</h4>
            <div class="d-flex mb-3">
                @include('transactionmanagement::admin.reports.partials._report-tabs', ['active' => 'pix'])
            </div>

            @include('transactionmanagement::admin.transaction.partials._pix-transaction-list')
        </div>
    </div>
@endsection
