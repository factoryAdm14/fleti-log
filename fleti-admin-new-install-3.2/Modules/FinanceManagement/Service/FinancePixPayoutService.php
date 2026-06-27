<?php

namespace Modules\FinanceManagement\Service;

use Illuminate\Support\Facades\Log;
use Modules\FinanceManagement\Contracts\PixPayoutGatewayInterface;
use Modules\FinanceManagement\DTO\PixPayoutResult;
use Modules\FinanceManagement\Entities\FinancePixPayoutLog;
use Modules\FinanceManagement\Gateways\EfiPixPayoutGateway;
use Modules\FinanceManagement\Gateways\MercadoPagoPixPayoutGateway;
use Modules\FinanceManagement\Lib\PixKeyResolver;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;
use Modules\UserManagement\Entities\WithdrawRequest;

class FinancePixPayoutService
{
    /** @var array<string, PixPayoutGatewayInterface> */
    private array $gateways = [];

    public function __construct(
        EfiPixPayoutGateway $efiPixPayoutGateway,
        MercadoPagoPixPayoutGateway $mercadoPagoPixPayoutGateway,
        private readonly FinanceSettingServiceInterface $financeSettingService,
        private readonly FinanceAuditService $financeAuditService,
    ) {
        foreach ([$efiPixPayoutGateway, $mercadoPagoPixPayoutGateway] as $gateway) {
            $this->gateways[$gateway->key()] = $gateway;
        }
    }

    public function isAutoPayoutEnabled(): bool
    {
        return (bool) $this->financeSettingService->get()->auto_pix_payout_enabled;
    }

    public function attemptPayout(WithdrawRequest $withdraw): PixPayoutResult
    {
        if ($withdraw->pix_end_to_end_id) {
            return PixPayoutResult::success(
                gateway: $withdraw->pix_payout_gateway ?? 'efi_pix',
                endToEndId: $withdraw->pix_end_to_end_id,
                reference: $withdraw->pix_payout_reference,
                status: 'already_sent',
            );
        }

        $pixKey = PixKeyResolver::fromWithdrawRequest($withdraw);
        if (!$pixKey) {
            $result = PixPayoutResult::failed('unknown', 'Chave PIX do motorista não encontrada nos dados do saque.');
            $this->recordAttempt($withdraw, $result);

            return $result;
        }

        $gateway = $this->resolveGateway();
        if (!$gateway) {
            $result = PixPayoutResult::failed('unknown', 'Nenhum gateway PIX disponível para pagamento.');
            $this->recordAttempt($withdraw, $result);

            return $result;
        }

        $withdraw->update([
            'pix_payout_status' => 'processing',
            'pix_payout_gateway' => $gateway->key(),
        ]);

        try {
            $result = $gateway->sendDriverPayout($withdraw, $pixKey);
        } catch (\Throwable $e) {
            Log::error('PIX payout exception', [
                'withdraw_id' => $withdraw->id,
                'gateway' => $gateway->key(),
                'error' => $e->getMessage(),
            ]);

            $result = PixPayoutResult::failed($gateway->key(), $e->getMessage());
        }

        $this->recordAttempt($withdraw, $result, $pixKey);

        return $result;
    }

    private function resolveGateway(): ?PixPayoutGatewayInterface
    {
        $settings = $this->financeSettingService->get();
        $preferred = $settings->primary_gateway === 'efi' ? 'efi_pix' : 'mercadopago_pix';
        $fallback = $preferred === 'efi_pix' ? 'mercadopago_pix' : 'efi_pix';

        foreach ([$preferred, $fallback] as $key) {
            $gateway = $this->gateways[$key] ?? null;
            if ($gateway?->isAvailable()) {
                return $gateway;
            }
        }

        return null;
    }

    private function recordAttempt(WithdrawRequest $withdraw, PixPayoutResult $result, ?string $pixKey = null): void
    {
        $withdraw->update([
            'pix_payout_gateway' => $result->gateway,
            'pix_payout_status' => $result->success ? ($result->status ?? 'sent') : 'failed',
            'pix_payout_reference' => $result->reference ?? $withdraw->pix_payout_reference,
            'pix_end_to_end_id' => $result->endToEndId,
        ]);

        FinancePixPayoutLog::query()->create([
            'withdraw_request_id' => $withdraw->id,
            'gateway' => $result->gateway,
            'event' => $result->success ? 'pix_payout_sent' : 'pix_payout_failed',
            'payload' => [
                'success' => $result->success,
                'end_to_end_id' => $result->endToEndId,
                'reference' => $result->reference,
                'status' => $result->status,
                'message' => $result->message,
                'pix_key_masked' => $pixKey ? self::maskPixKey($pixKey) : null,
            ],
        ]);

        $this->financeAuditService->log(
            action: $result->success ? 'pix_payout_sent' : 'pix_payout_failed',
            entityType: WithdrawRequest::class,
            entityId: (string) $withdraw->id,
            after: [
                'gateway' => $result->gateway,
                'end_to_end_id' => $result->endToEndId,
                'reference' => $result->reference,
                'message' => $result->message,
            ],
        );
    }

    private static function maskPixKey(string $pixKey): string
    {
        if (strlen($pixKey) <= 6) {
            return '***';
        }

        return substr($pixKey, 0, 3) . '***' . substr($pixKey, -3);
    }
}
