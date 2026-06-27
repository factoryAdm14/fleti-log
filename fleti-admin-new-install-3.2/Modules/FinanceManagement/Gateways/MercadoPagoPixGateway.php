<?php

namespace Modules\FinanceManagement\Gateways;

use Illuminate\Http\Request;
use Modules\FinanceManagement\Contracts\PaymentGatewayInterface;
use Modules\FinanceManagement\DTO\WebhookProcessingResult;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\Gateways\Services\MercadoPagoPixService;

class MercadoPagoPixGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly MercadoPagoPixService $pixService,
    ) {
    }

    public function key(): string
    {
        return 'mercadopago_pix';
    }

    public function displayName(): string
    {
        return 'Mercado Pago PIX';
    }

    public function supportsPix(): bool
    {
        return true;
    }

    public function supportsCard(): bool
    {
        return false;
    }

    public function isActive(): bool
    {
        return $this->pixService->isGatewayActive();
    }

    public function createPixPayment(PaymentRequest $payment): array
    {
        $config = $this->pixService->resolveConfig();
        if (!$config) {
            throw new \RuntimeException('Mercado Pago PIX não configurado.');
        }

        return $this->pixService->createOrGetPixPayment($payment, $config);
    }

    public function refreshPixStatus(PaymentRequest $payment): array
    {
        $config = $this->pixService->resolveConfig();
        if (!$config) {
            return ['status' => 'failed'];
        }

        return $this->pixService->refreshPaymentStatus($payment, $config);
    }

    public function processWebhook(array $payload, ?Request $request = null): WebhookProcessingResult
    {
        $config = $this->pixService->resolveConfig();
        if (!$config) {
            return WebhookProcessingResult::rejected($this->key(), 'Gateway inativo');
        }

        $this->pixService->processWebhookPayload($payload, $config);

        return WebhookProcessingResult::accepted($this->key());
    }

    public function verifyWebhook(Request $request): bool
    {
        $config = $this->pixService->resolveConfig();
        if (!$config) {
            return false;
        }

        $dataId = (string) ($request->input('data.id') ?? $request->input('id') ?? '');

        return $this->pixService->verifyWebhookSignature(
            $request->header('x-signature'),
            $request->header('x-request-id'),
            $dataId,
            $config->webhook_secret ?? null,
        );
    }
}
