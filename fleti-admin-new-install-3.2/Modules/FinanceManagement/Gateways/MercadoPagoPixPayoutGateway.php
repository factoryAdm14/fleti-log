<?php

namespace Modules\FinanceManagement\Gateways;

use Modules\FinanceManagement\Contracts\PixPayoutGatewayInterface;
use Modules\FinanceManagement\DTO\PixPayoutResult;
use Modules\Gateways\Services\MercadoPagoPixService;
use Modules\UserManagement\Entities\WithdrawRequest;

class MercadoPagoPixPayoutGateway implements PixPayoutGatewayInterface
{
    public function __construct(
        private readonly MercadoPagoPixService $pixService,
    ) {
    }

    public function key(): string
    {
        return 'mercadopago_pix';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function sendDriverPayout(WithdrawRequest $withdraw, string $pixKey): PixPayoutResult
    {
        return PixPayoutResult::failed(
            gateway: $this->key(),
            message: 'Pagamento PIX automático via Mercado Pago ainda não disponível. Use EFI ou liquidação manual.',
        );
    }
}
