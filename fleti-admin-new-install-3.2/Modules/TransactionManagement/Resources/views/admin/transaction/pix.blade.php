@extends('adminmodule::layouts.master')

@section('title', translate('pix_transactions'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between gap-3 align-items-center mb-4">
                <h2 class="fs-22 text-capitalize">{{ translate('pix_transactions') }}</h2>
                <div class="d-flex align-items-center gap-2 text-capitalize">
                    <span class="text-muted">{{ translate('total_records') }} :</span>
                    <span class="text-primary fs-16 fw-bold">{{ $payments->total() }}</span>
                </div>
            </div>

            @include('transactionmanagement::admin.transaction.partials._pix-transaction-list')
        </div>
    </div>
@endsection
