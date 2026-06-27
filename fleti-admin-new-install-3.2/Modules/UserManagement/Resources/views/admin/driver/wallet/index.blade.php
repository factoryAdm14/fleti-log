@extends('adminmodule::layouts.master')

@section('title', translate('driver_wallet'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            @can('user_add')
                <div class="d-flex justify-content-between gap-3 align-items-center mb-4">
                    <h2 class="fs-22 text-capitalize">{{ translate('add_fund') }}</h2>
                </div>

                <div class="alert alert-info mb-4">
                    {{ translate('driver_wallet_withdrawable_hint') }}
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <form action="{{ route('admin.driver.wallet.store') }}" method="post" id="formSubmit">
                            @csrf
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="mb-4">
                                        <label for="driver" class="mb-2">{{ translate('driver') }}</label>
                                        <select name="driver_id" id="driver" class="js-select-driver cmn_focus" required>
                                            <option selected disabled>-- {{ translate('select_driver') }} --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="mb-4">
                                        <label for="amount" class="mb-2">{{ translate('amount') }}</label>
                                        <input type="number" name="amount" value="{{ old('amount') }}" id="amount"
                                               class="form-control" step=".01" max="9999999" placeholder="Ex: 100" required>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="mb-4">
                                        <label for="reference" class="mb-2">{{ translate('reference') }}
                                            ({{ translate('optional') }})</label>
                                        <textarea name="reference" class="form-control" maxlength="800" id="reference"
                                                  rows="4">{{ old('reference') }}</textarea>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-3 mt-3">
                                    <button class="btn btn-primary cmn_focus" id="addWallet" type="submit">{{ translate('save') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="d-flex justify-content-between gap-3 align-items-center mb-4">
                <h2 class="fs-22 text-capitalize">{{ translate('driver_wallet_report') }}</h2>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="fw-semibold text-primary text-uppercase mb-4">{{ translate('filter_data') }}</h6>
                    <form method="GET" action="{{ url()->full() }}">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="mb-4">
                                    <label for="driver1" class="mb-2">{{ translate('driver') }}</label>
                                    <select id="driver1" class="js-select-driver-filter cmn_focus" name="user_id">
                                        <option selected disabled>-- {{ translate('select_driver') }} --</option>
                                        @if(request()->get('user_id') && request()->get('user_id') == 'all')
                                            <option value="all" selected>{{ translate('all_driver') }}</option>
                                        @endif
                                        @if (request()->get('user_id') && $driver_info = Modules\UserManagement\Entities\User::find(request()->get('user_id')))
                                            <option value="{{ $driver_info->id }}" selected>
                                                {{ $driver_info?->first_name . ' ' . $driver_info?->last_name }}
                                                ({{ $driver_info->phone }})
                                            </option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-4">
                                    <label for="dateRange" class="mb-2">{{ translate('date_range') }}</label>
                                    <select name="data" id="dateRange" class="js-select cmn_focus">
                                        <option value="0" disabled selected>{{ translate('Date_Range') }}</option>
                                        <option value="{{ ALL_TIME }}" @selected(request('data') == ALL_TIME)>{{ translate(ALL_TIME) }}</option>
                                        <option value="{{ THIS_WEEK }}" @selected(request('data') == THIS_WEEK)>{{ translate(THIS_WEEK) }}</option>
                                        <option value="{{ LAST_WEEK }}" @selected(request('data') == LAST_WEEK)>{{ translate(LAST_WEEK) }}</option>
                                        <option value="{{ THIS_MONTH }}" @selected(request('data') == THIS_MONTH)>{{ translate(THIS_MONTH) }}</option>
                                        <option value="{{ LAST_MONTH }}" @selected(request('data') == LAST_MONTH)>{{ translate(LAST_MONTH) }}</option>
                                        <option value="{{ CUSTOM_DATE }}" @selected(request('data') == CUSTOM_DATE)>{{ translate(CUSTOM_DATE) }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6 {{ request('data') == CUSTOM_DATE ? '' : 'd-none' }}" id="fromFilterDiv">
                                <label for="from">{{ translate('From') }}</label>
                                <input type="date" class="form-control" id="from" name="start" value="{{ request('start') }}">
                            </div>
                            <div class="col-sm-6 {{ request('data') == CUSTOM_DATE ? '' : 'd-none' }}" id="toFilterDiv">
                                <label for="to">{{ translate('To') }}</label>
                                <input type="date" class="form-control" id="to" name="end" value="{{ request('end') }}">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-3 mt-3">
                            <button class="btn btn-primary cmn_focus" type="submit">{{ translate('filter') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="table-top d-flex flex-wrap gap-10 justify-content-between">
                        <form action="javascript:;" method="GET" class="search-form search-form_style-two">
                            <div class="input-group search-form__input_group">
                                <span class="search-form__icon"><i class="bi bi-search"></i></span>
                                <input type="search" name="search" value="{{ request('search') }}"
                                       class="theme-input-style search-form__input"
                                       placeholder="{{ translate('Search_here_by_Transaction_Id') }}">
                            </div>
                            <button type="submit" class="btn btn-primary search-submit" data-url="{{ url()->full() }}">{{ translate('search') }}</button>
                        </form>
                        @can('user_export')
                            <a class="btn btn-outline-primary"
                               href="{{ route('admin.driver.wallet.export', ['user_id' => request('user_id'), 'data' => request('data'), 'start' => request('start'), 'end' => request('end'), 'search' => request('search'), 'file' => 'excel']) }}">
                                <i class="bi bi-download"></i> {{ translate('download') }}
                            </a>
                        @endcan
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-borderless align-middle table-hover text-nowrap">
                            <thead class="table-light align-middle text-capitalize">
                            <tr>
                                <th>{{ translate('SL') }}</th>
                                <th class="text-center">{{ translate('driver') }}</th>
                                <th class="text-center">{{ translate('phone') }}</th>
                                <th class="text-center">{{ translate('transaction_id') }}</th>
                                <th class="text-center">{{ translate('reference') }}</th>
                                <th class="text-center">{{ translate('transaction_date') }}</th>
                                <th class="text-center">{{ translate('credit') }}</th>
                                <th class="text-center">{{ translate('withdrawable_balance') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($transactions as $key => $transaction)
                                <tr>
                                    <td>{{ $key + $transactions->firstItem() }}</td>
                                    <td class="text-center">{{ trim(($transaction?->user?->first_name ?? '') . ' ' . ($transaction?->user?->last_name ?? '')) ?: 'N/A' }}</td>
                                    <td class="text-center">{{ $transaction?->user?->phone ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $transaction->id }}</td>
                                    <td class="text-center">{{ \Illuminate\Support\Str::limit($transaction->reference, 30) }}</td>
                                    <td class="text-center">{{ $transaction->created_at }}</td>
                                    <td class="text-center">{{ set_currency_symbol($transaction->credit) }}</td>
                                    <td class="text-center">{{ set_currency_symbol($transaction->balance) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
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

                    <div class="d-flex justify-content-end mt-3">
                        {!! $transactions->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="{{ dynamicAsset('public/assets/admin-module/js/user-management/driver/wallet/index.js') }}"></script>
    <script>
        "use strict";

        const driverSelectConfig = {
            allowClear: false,
            ajax: {
                url: '{{ route('admin.driver.get-all-ajax') }}',
                data: function (params) {
                    return {
                        search: params.term,
                        page: params.page,
                        all_driver: 1
                    };
                },
                processResults: function (data) {
                    return { results: data };
                }
            }
        };

        $('.js-select-driver').select2({
            ...driverSelectConfig,
            placeholder: "{{ translate('select_driver') }}"
        });

        $('.js-select-driver-filter').select2({
            ...driverSelectConfig,
            placeholder: "{{ translate('select_driver') }}"
        });

        let driverId = @json(request('user_id'));
        if (driverId) {
            $('.js-select-driver-filter').val(driverId).trigger('change');
        }
    </script>
@endpush
