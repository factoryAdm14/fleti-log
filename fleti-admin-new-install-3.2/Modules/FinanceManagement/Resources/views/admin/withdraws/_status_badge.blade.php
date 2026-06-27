@php
    $badgeClass = match ($status) {
        SETTLED => 'success',
        APPROVED => 'info',
        DENIED => 'danger',
        default => 'warning',
    };
    $label = match ($status) {
        SETTLED => 'Pago',
        APPROVED => 'Aprovado',
        DENIED => 'Recusado',
        default => 'Pendente',
    };
@endphp
@include('financemanagement::admin.partials._badge', ['status' => $status, 'label' => $label])
