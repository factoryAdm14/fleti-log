@php
    $variant = match ($status) {
        SETTLED, 'active', 'completed', 'paid' => 'success',
        APPROVED, 'approved' => 'info',
        DENIED, 'denied', 'cancelled', 'failed', 'expired' => 'danger',
        'pending', PENDING => 'warning',
        'neutral' => 'neutral',
        default => 'neutral',
    };

    $label = $label ?? match ($status) {
        SETTLED => 'Pago',
        APPROVED => 'Aprovado',
        DENIED => 'Recusado',
        PENDING => 'Pendente',
        'active' => 'Ativo',
        'pending' => 'Pendente',
        'expired' => 'Expirado',
        'cancelled' => 'Cancelado',
        'failed' => 'Falhou',
        default => ucfirst((string) $status),
    };
@endphp
<span class="fin-badge fin-badge--{{ $variant }}">{{ $label }}</span>
