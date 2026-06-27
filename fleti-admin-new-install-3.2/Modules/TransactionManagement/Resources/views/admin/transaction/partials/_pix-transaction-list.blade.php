@php
    $exportRoute = ($showReportTabs ?? false)
        ? route('admin.report.pixReportExport', array_merge(['file' => 'excel'], request()->all()))
        : route('admin.transaction.pix.export', array_merge(['file' => 'excel'], request()->all()));
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted d-block mb-1">{{ translate('total_pix_transactions') }}</span>
                <h4 class="mb-0 text-primary">{{ $summary['total'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted d-block mb-1">{{ translate('paid') }}</span>
                <h4 class="mb-0 text-success">{{ $summary['paid_count'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted d-block mb-1">{{ translate('pending') }}</span>
                <h4 class="mb-0 text-warning">{{ $summary['pending_count'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <span class="text-muted d-block mb-1">{{ translate('paid_amount') }}</span>
                <h4 class="mb-0 text-primary">{{ getCurrencyFormat($summary['paid_amount'] ?? 0) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ url()->current() }}" method="GET" class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label class="form-label">{{ translate('search') }}</label>
                <input type="search" name="search" value="{{ request('search') }}" class="form-control"
                       placeholder="{{ translate('search_by_payment_or_tx_id') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ translate('gateway') }}</label>
                <select name="gateway" class="js-select theme-input-style w-100">
                    <option value="all">{{ translate('all') }}</option>
                    <option value="efi_pix" @selected(request('gateway') === 'efi_pix')>{{ translate('efi_pix') }}</option>
                    <option value="mercadopago_pix" @selected(request('gateway') === 'mercadopago_pix')>{{ translate('mercadopago_pix') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ translate('status') }}</label>
                <select name="is_paid" class="js-select theme-input-style w-100">
                    <option value="all">{{ translate('all') }}</option>
                    <option value="paid" @selected(request('is_paid') === 'paid')>{{ translate('paid') }}</option>
                    <option value="pending" @selected(request('is_paid') === 'pending')>{{ translate('pending') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ translate('context') }}</label>
                <select name="context" class="js-select theme-input-style w-100">
                    <option value="all">{{ translate('all') }}</option>
                    <option value="add_wallet_amount_digitally" @selected(request('context') === 'add_wallet_amount_digitally')>{{ translate('wallet_top_up') }}</option>
                    <option value="order" @selected(request('context') === 'order')>{{ translate('trip_payment') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ translate('date_range') }}</label>
                <select name="date_range" class="js-select theme-input-style w-100">
                    <option value="{{ ALL_TIME }}" @selected(request('date_range', ALL_TIME) === ALL_TIME)>{{ translate(ALL_TIME) }}</option>
                    <option value="{{ TODAY }}" @selected(request('date_range') === TODAY)>{{ translate(TODAY) }}</option>
                    <option value="{{ LAST_7_DAYS }}" @selected(request('date_range') === LAST_7_DAYS)>{{ translate(LAST_7_DAYS) }}</option>
                    <option value="{{ THIS_MONTH }}" @selected(request('date_range') === THIS_MONTH)>{{ translate(THIS_MONTH) }}</option>
                    <option value="{{ LAST_MONTH }}" @selected(request('date_range') === LAST_MONTH)>{{ translate(LAST_MONTH) }}</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">{{ translate('filter') }}</button>
            </div>
        </form>

        @can('transaction_export')
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ $exportRoute }}" class="btn btn-outline-primary">
                    <i class="bi bi-download"></i> {{ translate('download') }}
                </a>
            </div>
        @endcan

        <div class="table-responsive">
            <table class="table table-borderless align-middle table-hover text-nowrap">
                <thead class="table-light align-middle text-capitalize">
                <tr>
                    <th>{{ translate('SL') }}</th>
                    <th class="text-center">{{ translate('date') }}</th>
                    <th class="text-center">{{ translate('gateway') }}</th>
                    <th class="text-center">{{ translate('external_tx_id') }}</th>
                    <th class="text-center">{{ translate('customer') }}</th>
                    <th class="text-center">{{ translate('context') }}</th>
                    <th class="text-center">{{ translate('reference') }}</th>
                    <th class="text-center">{{ translate('amount') }}</th>
                    <th class="text-center">{{ translate('status') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($payments as $key => $payment)
                    <tr>
                        <td>{{ $payments->firstItem() + $key }}</td>
                        <td class="text-center">{{ date('d-m-Y h:i A', strtotime($payment->created_at)) }}</td>
                        <td class="text-center">{{ $pixService->gatewayLabel($payment->payment_method) }}</td>
                        <td class="text-center">{{ $payment->transaction_id ?: '-' }}</td>
                        <td class="text-center">
                            {{ trim(($payment->payer?->first_name ?? '') . ' ' . ($payment->payer?->last_name ?? '')) ?: '-' }}
                            @if($payment->payer?->phone)
                                <small class="opacity-75 d-block">{{ $payment->payer->phone }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $pixService->contextLabel($payment->attribute) }}</td>
                        <td class="text-center">{{ $payment->attribute_id ?: '-' }}</td>
                        <td class="text-center">{{ getCurrencyFormat($payment->payment_amount) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $payment->is_paid ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $pixService->statusLabel($payment) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-3">
                                <img src="{{ dynamicAsset('public/assets/admin-module/img/empty-icons/no-data-found.svg') }}" alt="" width="100">
                                <p class="text-center mb-0">{{ translate('no_data_available') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-end gap-3 mt-3">
            {!! $payments->links() !!}
        </div>
    </div>
</div>
