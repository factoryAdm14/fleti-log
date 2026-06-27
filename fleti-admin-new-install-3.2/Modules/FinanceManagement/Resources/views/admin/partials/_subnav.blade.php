@php
    $current = request()->route()->getName();
    $items = [
        ['route' => 'admin.finance.dashboard.index', 'label' => 'Dashboard', 'icon' => 'bi-graph-up-arrow', 'match' => 'admin.finance.dashboard.*'],
        ['route' => 'admin.finance.audit.index', 'label' => 'Auditoria', 'icon' => 'bi-journal-text', 'match' => 'admin.finance.audit.*'],
        ['route' => 'admin.finance.settings.index', 'label' => 'Configurações', 'icon' => 'bi-gear', 'match' => 'admin.finance.settings.*'],
        ['route' => 'admin.finance.withdraws.index', 'label' => 'Saques', 'icon' => 'bi-wallet2', 'match' => 'admin.finance.withdraws.*'],
        ['route' => 'admin.finance.plans.index', 'label' => 'Planos', 'icon' => 'bi-card-checklist', 'match' => 'admin.finance.plans.*'],
        ['route' => 'admin.finance.subscriptions.index', 'label' => 'Assinaturas', 'icon' => 'bi-people', 'match' => 'admin.finance.subscriptions.*'],
    ];
@endphp
<nav class="fin-subnav" aria-label="Navegação financeira">
    @foreach($items as $item)
        <a href="{{ route($item['route']) }}"
           class="{{ request()->routeIs($item['match']) ? 'is-active' : '' }}"
           title="{{ $item['label'] }}">
            <i class="bi {{ $item['icon'] }}"></i>
            <span class="fin-subnav-label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
