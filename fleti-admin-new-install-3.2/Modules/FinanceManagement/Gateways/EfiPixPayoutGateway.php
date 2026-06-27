<?php

namespace Modules\FinanceManagement\Gateways;

use Modules\FinanceManagement\Contracts\PixPayoutGatewayInterface;
use Modules\FinanceManagement\DTO\PixPayoutResult;
use Modules\Gateways\Services\EfiPixPayoutService;
use Modules\UserManagement\Entities\WithdrawRequest;

class EfiPixPayoutGateway implements PixPayoutGatewayInterface
{
    public function __construct(
        private readonly EfiPixPayoutService $payoutService,
    ) {
    }

    public function key(): string
    {
        return 'efi_pix';
    }

    public function isAvailable(): bool
    {
        return $this->payoutService->isGatewayActive()
            && $this->payoutService->resolveConfig() !== null;
    }

    public function sendDriverPayout(WithdrawRequest $withdraw, string $pixKey): PixPayoutResult
    {
        $idEnvio = $withdraw->pix_payout_reference
            ?? $this->payoutService->buildIdEnvio($withdraw->id);

        $result = $this->payoutService->sendPix(
            idEnvio: $idEnvio,
            amount: (float) $withdraw->amount,
            destinationPixKey: $pixKey,
            info: 'Saque Fleti #' . $withdraw->id,
        );

        if (!$result['success']) {
            return PixPayoutResult::failed(
                gateway: $this->key(),
                message: $result['message'] ?? 'Falha no envio PIX EFI.',
            );
        }

        return PixPayoutResult::success(
            gateway: $this->key(),
            endToEndId: $result['end_to_end_id'] ?? null,
            reference: $result['reference'] ?? $idEnvio,
            status: $result['status'] ?? 'sent',
        );
    }
}
