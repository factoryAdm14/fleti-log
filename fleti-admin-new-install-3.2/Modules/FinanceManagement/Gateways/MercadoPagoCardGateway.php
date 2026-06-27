<?php

namespace Modules\FinanceManagement\Gateways;

use Illuminate\Http\Request;
use Modules\FinanceManagement\Contracts\PaymentGatewayInterface;
use Modules\FinanceManagement\DTO\WebhookProcessingResult;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\Gateways\Traits\Processor;

class MercadoPagoCardGateway implements PaymentGatewayInterface
{
    use Processor;

    public function key(): string
    {
        return 'mercadopago';
    }

    public function displayName(): string
    {
        return 'Mercado Pago Cartão';
    }

    public function supportsPix(): bool
    {
        return false;
    }

    public function supportsCard(): bool
    {
        return true;
    }

    public function isActive(): bool
    {
        $config = $this->paymentConfig('mercadopago', PAYMENT_CONFIG);

        return !is_null($config) && (int) $config->is_active === 1;
    }

    public function createPixPayment(PaymentRequest $payment): array
    {
        throw new \BadMethodCallException('Mercado Pago cartão não suporta PIX.');
    }

    public function refreshPixStatus(PaymentRequest $payment): array
    {
        return ['status' => $payment->is_paid ? 'paid' : 'pending'];
    }

    public function processWebhook(array $payload, ?Request $request = null): WebhookProcessingResult
    {
        return WebhookProcessingResult::rejected($this->key(), 'Webhook não utilizado para cartão síncrono.');
    }

    public function verifyWebhook(Request $request): bool
    {
        return true;
    }
}
