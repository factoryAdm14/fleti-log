@extends('financemanagement::admin.layout')

@section('title', 'Auditoria Financeira')

@section('finance_content')
    @include('financemanagement::admin.partials._page_header', [
        'title' => 'Auditoria',
        'subtitle' => 'Log de ações e transações financeiras',
    ])

    <form method="get" class="fin-form-card mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Ação</label>
                <input type="text" name="action" class="form-control form-control-sm" value="{{ request('action') }}" placeholder="withdraw_approved">
            </div>
            <div class="col-md-3">
                <label class="form-label">Entidade</label>
                <input type="text" name="entity_type" class="form-control form-control-sm" value="{{ request('entity_type') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">De</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Até</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="fin-btn fin-btn--primary fin-btn--sm">Filtrar</button>
                <a href="{{ route('admin.finance.audit.index') }}" class="fin-btn fin-btn--ghost fin-btn--sm">Limpar</a>
            </div>
        </div>
    </form>

    <div class="fin-panel">
        <div class="table-responsive">
            <table class="table fin-table mb-0">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Ação</th>
                    <th>Usuário</th>
                    <th>IP</th>
                    <th>Entidade</th>
                    <th>Notas</th>
                </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-nowrap" style="font-size:.8125rem">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        <td><span class="fin-badge fin-badge--neutral">{{ $log->action }}</span></td>
                        <td style="font-size:.8125rem">
                            {{ $log->user_type ?? 'system' }}
                            @if($log->user_id)
                                <div class="text-muted">{{ Str::limit($log->user_id, 8) }}</div>
                            @endif
                        </td>
                        <td style="font-size:.8125rem">{{ $log->ip_address ?? '—' }}</td>
                        <td style="font-size:.8125rem">
                            @if($log->entity_type)
                                <div>{{ class_basename($log->entity_type) }}</div>
                                @if($log->entity_id)
                                    <div class="text-muted">{{ Str::limit($log->entity_id, 12) }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:.8125rem">{{ Str::limit($log->notes ?? '', 80) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">@include('financemanagement::admin.partials._empty')</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="fin-panel-body border-top" style="border-color:var(--fin-border)!important">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
