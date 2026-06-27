<?php

namespace Modules\FinanceManagement\Contracts;

use Illuminate\Http\Request;
use Modules\FinanceManagement\DTO\WebhookProcessingResult;
use Modules\Gateways\Entities\PaymentRequest;

interface PaymentGatewayInterface
{
    public function key(): string;

    public function displayName(): string;

    public function supportsPix(): bool;

    public function supportsCard(): bool;

    public function isActive(): bool;

    /**
     * @return array{status: string, qr_code?: string|null, qr_code_base64?: string|null, expires_at?: string|null, gateway_fee?: float}
     */
    public function createPixPayment(PaymentRequest $payment): array;

    /**
     * @return array{status: string, gateway_fee?: float}
     */
    public function refreshPixStatus(PaymentRequest $payment): array;

    public function processWebhook(array $payload, ?Request $request = null): WebhookProcessingResult;

    public function verifyWebhook(Request $request): bool;
}
