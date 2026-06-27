@extends('financemanagement::admin.layout')

@section('title', 'Assinaturas de Motoristas')

@section('finance_content')
    @include('financemanagement::admin.partials._page_header', [
        'title' => 'Assinaturas',
        'subtitle' => 'Planos ativos dos motoristas',
    ])

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="fin-form-card">
                <h5>Ativar manualmente</h5>
                <form method="POST" action="{{ route('admin.finance.subscriptions.activate') }}" id="manual-subscription-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="subscription-driver-select">Motorista</label>
                        <div class="d-flex gap-2 align-items-start">
                            <div class="flex-grow-1">
                                <select name="driver_id" id="subscription-driver-select" class="form-select form-select-sm" required>
                                    <option value=""></option>
                                </select>
                            </div>
                            <button type="button" class="fin-btn fin-btn--outline fin-btn--sm flex-shrink-0" id="subscription-driver-search-btn" title="Pesquisar motorista">
                                <i class="bi bi-search"></i> Pesquisar
                            </button>
                        </div>
                        <div class="form-text mt-1" id="subscription-driver-uuid-hint" style="display:none">
                            ID do motorista: <code id="subscription-driver-uuid"></code>
                        </div>
                        <div class="form-text">Busque por nome, telefone ou ID (UUID).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plano</label>
                        <select name="plan_id" class="form-select form-select-sm" required>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} — {{ set_currency_symbol($plan->price) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="fin-btn fin-btn--success fin-btn--sm">Ativar assinatura</button>
                </form>
            </div>
        </div>
    </div>

    <div class="fin-panel">
        <div class="fin-panel-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <ul class="fin-tabs">
                    @foreach(['all' => 'Todos', 'active' => 'Ativos', 'pending' => 'Pendentes', 'expired' => 'Expirados', 'cancelled' => 'Cancelados'] as $key => $label)
                        <li>
                            <a href="{{ route('admin.finance.subscriptions.index', ['status' => $key]) }}"
                               class="{{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Nome, telefone ou ID do motorista...">
                    <button class="fin-btn fin-btn--primary fin-btn--sm" type="submit">Buscar</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table fin-table mb-0">
                    <thead>
                    <tr>
                        <th>Motorista</th>
                        <th>Plano</th>
                        <th>Início</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($subscriptions as $sub)
                        <tr>
                            <td>
                                @if($sub->driver)
                                    <div class="fw-semibold">{{ $sub->driver->first_name }} {{ $sub->driver->last_name }}</div>
                                    <div class="text-muted" style="font-size:.75rem">{{ $sub->driver->phone }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $sub->plan?->name ?? '—' }}</td>
                            <td>{{ $sub->starts_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $sub->expires_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @include('financemanagement::admin.partials._badge', ['status' => $sub->status])
                            </td>
                            <td class="text-end">
                                @if(in_array($sub->status, ['active', 'pending']))
                                    <form method="POST" action="{{ route('admin.finance.subscriptions.cancel', $sub->id) }}" class="d-inline" onsubmit="return confirm('Cancelar esta assinatura?')">
                                        @csrf
                                        <button type="submit" class="fin-btn fin-btn--danger fin-btn--sm">Cancelar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">@include('financemanagement::admin.partials._empty')</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $subscriptions->links() }}</div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";

        $(function () {
            const $driverSelect = $('#subscription-driver-select');
            const $uuidHint = $('#subscription-driver-uuid-hint');
            const $uuidText = $('#subscription-driver-uuid');

            $driverSelect.select2({
                width: '100%',
                allowClear: true,
                placeholder: 'Digite para buscar motorista...',
                minimumInputLength: 1,
                ajax: {
                    url: '{{ route('admin.finance.subscriptions.search-drivers') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { search: params.term || '' };
                    },
                    processResults: function (data) {
                        return { results: data };
                    }
                }
            });

            $driverSelect.on('change', function () {
                const driverId = $(this).val();
                if (driverId) {
                    $uuidText.text(driverId);
                    $uuidHint.show();
                } else {
                    $uuidHint.hide();
                }
            });

            $('#subscription-driver-search-btn').on('click', function () {
                $driverSelect.select2('open');
            });
        });
    </script>
@endpush
