@extends('financemanagement::admin.layout')

@section('title', translate('Finance_Dashboard'))

@section('finance_content')
    <div class="fin-page-head">
        <div>
            <h2>Dashboard financeiro</h2>
            <p>{{ $stats['period_label'] }}</p>
        </div>
        <div class="fin-actions">
            <form method="get" class="d-flex">
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($periodOptions as $value => $label)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @php
        $moneyGroups = [
            ['title' => 'Receitas', 'items' => [
                ['label' => 'Receita total', 'value' => $stats['total_revenue'], 'tone' => 'success'],
                ['label' => 'Receita por comissão', 'value' => $stats['commission_revenue'], 'tone' => 'info'],
                ['label' => 'Receita por planos', 'value' => $stats['plan_revenue'], 'tone' => 'info'],
                ['label' => 'Pago aos motoristas', 'value' => $stats['driver_paid'], 'tone' => 'info'],
                ['label' => 'Taxas do gateway', 'value' => $stats['gateway_fees'], 'tone' => 'danger'],
                ['label' => 'Lucro líquido estimado', 'value' => $stats['estimated_net_profit'], 'tone' => 'success'],
            ]],
            ['title' => 'Pagamentos digitais', 'items' => [
                ['label' => 'PIX recebido', 'value' => $stats['pix_received'], 'tone' => 'success'],
                ['label' => 'Cartão recebido', 'value' => $stats['card_received'], 'tone' => 'info'],
            ]],
            ['title' => 'Saques', 'items' => [
                ['label' => 'Saques pendentes', 'value' => $stats['pending_withdraws_amount'], 'tone' => 'warning', 'suffix' => $stats['pending_withdraws_count'] . ' solicitações'],
                ['label' => 'Saques aprovados', 'value' => $stats['approved_withdraws_amount'], 'tone' => 'info', 'suffix' => $stats['approved_withdraws_count'] . ' solicitações'],
                ['label' => 'Saques pagos', 'value' => $stats['settled_withdraws_amount'], 'tone' => 'success', 'suffix' => $stats['settled_withdraws_count'] . ' solicitações'],
            ]],
        ];

        $countGroups = [
            ['title' => 'Motoristas', 'items' => [
                ['label' => 'Com plano ativo', 'value' => $stats['drivers_with_active_plan'], 'tone' => 'success', 'money' => false],
                ['label' => 'Modo comissão', 'value' => $stats['drivers_commission_mode'], 'tone' => 'info', 'money' => false],
                ['label' => 'Assinaturas ativas', 'value' => $stats['active_subscriptions'], 'tone' => 'info', 'money' => false],
                ['label' => 'Planos disponíveis', 'value' => $stats['plans_count'], 'tone' => 'neutral', 'money' => false],
            ]],
            ['title' => 'Carteiras digitais', 'items' => [
                ['label' => 'Saldo disponível', 'value' => $stats['wallet_available'], 'tone' => 'success'],
                ['label' => 'Saldo pendente', 'value' => $stats['wallet_pending'], 'tone' => 'warning'],
                ['label' => 'Saldo bloqueado', 'value' => $stats['wallet_blocked'], 'tone' => 'danger'],
                ['label' => 'Transações', 'value' => $stats['transactions_count'], 'tone' => 'neutral', 'money' => false],
            ]],
        ];
    @endphp

    @foreach($moneyGroups as $group)
        <h6 class="fin-section-title">{{ $group['title'] }}</h6>
        <div class="row g-3 mb-4">
            @foreach($group['items'] as $card)
                <div class="col-md-6 col-xl-4 col-xxl-3">
                    @include('financemanagement::admin.partials._stat_card', $card)
                </div>
            @endforeach
        </div>
    @endforeach

    @foreach($countGroups as $group)
        <h6 class="fin-section-title">{{ $group['title'] }}</h6>
        <div class="row g-3 mb-3">
            @foreach($group['items'] as $card)
                <div class="col-md-6 col-xl-3">
                    @include('financemanagement::admin.partials._stat_card', $card)
                </div>
            @endforeach
        </div>
    @endforeach
@endsection
