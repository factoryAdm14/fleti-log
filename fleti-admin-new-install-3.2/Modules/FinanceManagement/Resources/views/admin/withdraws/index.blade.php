@extends('financemanagement::admin.layout')

@section('title', 'Saques Financeiros')

@section('finance_content')
    @include('financemanagement::admin.partials._page_header', [
        'title' => 'Saques',
        'subtitle' => 'Carteira digital dos motoristas',
    ])

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            @include('financemanagement::admin.partials._stat_card', [
                'label' => 'Pendentes',
                'value' => $counts['pending'],
                'tone' => 'warning',
                'money' => false,
            ])
        </div>
        <div class="col-md-4">
            @include('financemanagement::admin.partials._stat_card', [
                'label' => 'Aprovados',
                'value' => $counts['approved'],
                'tone' => 'info',
                'money' => false,
            ])
        </div>
        <div class="col-md-4">
            @include('financemanagement::admin.partials._stat_card', [
                'label' => 'Pagos',
                'value' => $counts['settled'],
                'tone' => 'success',
                'money' => false,
            ])
        </div>
    </div>

    <div class="fin-panel">
        <div class="fin-panel-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <ul class="fin-tabs">
                    @foreach(['all' => 'Todos', PENDING => 'Pendentes', APPROVED => 'Aprovados', SETTLED => 'Pagos', DENIED => 'Recusados'] as $key => $label)
                        <li>
                            <a href="{{ route('admin.finance.withdraws.index', array_merge(request()->except('page'), ['status' => $key])) }}"
                               class="{{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>

                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <input type="search" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm" placeholder="Buscar motorista...">
                    <button class="fin-btn fin-btn--primary fin-btn--sm" type="submit">Buscar</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table fin-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Motorista</th>
                        <th>Valor</th>
                        <th>Método / PIX</th>
                        <th>Solicitado</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $item)
                        @php
                            $pixKey = collect($item->method_fields ?? [])->first();
                            $pixLabel = collect($item->method_fields ?? [])->keys()->first();
                        @endphp
                        <tr>
                            <td>{{ $requests->firstItem() + $loop->index }}</td>
                            <td>
                                @if($item->user)
                                    <div class="fw-semibold">{{ $item->user->first_name }} {{ $item->user->last_name }}</div>
                                    <div class="text-muted" style="font-size:.75rem">{{ $item->user->phone }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="fin-money--info">{{ set_currency_symbol($item->amount) }}</td>
                            <td>
                                <div>{{ $item->method?->method_name ?? '—' }}</div>
                                @if($pixLabel)
                                    <div class="text-muted" style="font-size:.75rem">{{ ucfirst(str_replace('_', ' ', $pixLabel)) }}: {{ $pixKey }}</div>
                                @endif
                            </td>
                            <td>{{ $item->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                @include('financemanagement::admin.partials._badge', ['status' => $item->status])
                                @if($item->pix_payout_status === 'failed')
                                    <div style="font-size:.7rem;color:var(--fin-red);margin-top:.2rem">PIX falhou</div>
                                @elseif($item->pix_end_to_end_id)
                                    <div style="font-size:.7rem;color:var(--fin-green);margin-top:.2rem">PIX enviado</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="fin-btn fin-btn--outline fin-btn--sm"
                                        data-bs-toggle="modal" data-bs-target="#detailModal{{ $item->id }}">
                                    Detalhes
                                </button>
                            </td>
                        </tr>
                        @include('financemanagement::admin.withdraws._detail_modal', ['item' => $item])
                    @empty
                        <tr>
                            <td colspan="7">@include('financemanagement::admin.partials._empty')</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $requests->links() }}</div>
        </div>
    </div>
@endsection
