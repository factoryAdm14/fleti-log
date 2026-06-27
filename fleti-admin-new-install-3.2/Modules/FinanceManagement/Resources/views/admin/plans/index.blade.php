@extends('financemanagement::admin.layout')

@section('title', 'Planos de Motorista')

@section('finance_content')
    <div class="fin-page-head">
        <div>
            <h2>Planos</h2>
            <p>Assinaturas sem comissão por corrida</p>
        </div>
        <div class="fin-actions">
            <a href="{{ route('admin.finance.plans.create') }}" class="fin-btn fin-btn--primary fin-btn--sm">
                <i class="bi bi-plus-lg"></i> Novo plano
            </a>
        </div>
    </div>

    <div class="fin-panel">
        <div class="table-responsive">
            <table class="table fin-table mb-0">
                <thead>
                <tr>
                    <th>Plano</th>
                    <th>Preço</th>
                    <th>Duração</th>
                    <th>Comissão</th>
                    <th>Assinaturas</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
                </thead>
                <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $plan->name }}</div>
                            <div class="text-muted" style="font-size:.75rem">{{ \Illuminate\Support\Str::limit($plan->description, 60) }}</div>
                        </td>
                        <td class="fin-money--info">{{ set_currency_symbol($plan->price) }}</td>
                        <td>{{ $plan->duration_days }} dias</td>
                        <td>{{ $plan->commission_percentage }}%</td>
                        <td>{{ $plan->subscriptions_count }}</td>
                        <td>
                            @include('financemanagement::admin.partials._badge', [
                                'status' => $plan->is_active ? 'active' : 'neutral',
                                'label' => $plan->is_active ? 'Ativo' : 'Inativo',
                            ])
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.finance.plans.edit', $plan->id) }}" class="fin-btn fin-btn--outline fin-btn--sm">Editar</a>
                            <form method="POST" action="{{ route('admin.finance.plans.toggle', $plan->id) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="fin-btn fin-btn--ghost fin-btn--sm">
                                    {{ $plan->is_active ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">@include('financemanagement::admin.partials._empty')</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($plans->hasPages())
            <div class="fin-panel-body border-top" style="border-color:var(--fin-border)!important">
                {{ $plans->links() }}
            </div>
        @endif
    </div>
@endsection
