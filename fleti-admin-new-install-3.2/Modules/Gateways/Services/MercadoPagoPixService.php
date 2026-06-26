<?php

namespace Modules\Gateways\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Gateways\Entities\MercadoPagoPixLog;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\Gateways\Traits\Processor;

class MercadoPagoPixService
{
    use Processor;

    private const API_BASE = 'https://api.mercadopago.com';

    public function resolveConfig(): ?object
    {
        $config = $this->paymentConfig('mercadopago_pix', PAYMENT_CONFIG);
        if (is_null($config)) {
            return null;
        }

        if ($config->mode === 'live') {
            return json_decode($config->live_values);
        }

        return json_decode($config->test_values);
    }

    public function isGatewayActive(): bool
    {
        $config = $this->paymentConfig('mercadopago_pix', PAYMENT_CONFIG);

        return !is_null($config) && (int) $config->is_active === 1;
    }

    public function createOrGetPixPayment(PaymentRequest $payment, object $config): array
    {
        $pixMeta = $this->getPixMeta($payment);

        if (!empty($pixMeta['mp_payment_id'])) {
            $existing = $this->fetchPayment((string) $pixMeta['mp_payment_id'], $config);
            if ($existing) {
                $normalized = $this->normalizePixPayload($existing);
                $this->persistPixMeta($payment, $normalized);

                return $normalized;
            }
        }

        $payer = json_decode($payment->payer_information);
        $idempotencyKey = 'fleti-pix-' . $payment->id;

        $response = Http::withToken($config->access_token)
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->post(self::API_BASE . '/v1/payments', [
                'transaction_amount' => round((float) $payment->payment_amount, 2),
                'description' => 'Fleti ' . substr($payment->id, 0, 8),
                'payment_method_id' => 'pix',
                'external_reference' => $payment->id,
                'notification_url' => route('mercadopago_pix.webhook'),
                'payer' => [
                    'email' => $payer->email ?? 'noemail@fleti.com.br',
                    'first_name' => $payer->name ?? 'Cliente',
                ],
            ]);

        $this->audit($payment->id, 'create_payment', [
            'http_status' => $response->status(),
            'response' => $response->json(),
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Mercado Pago PIX: ' . ($response->json('message') ?? $response->body())
            );
        }

        $normalized = $this->normalizePixPayload($response->json());
        $this->persistPixMeta($payment, $normalized);
        $payment->transaction_id = (string) $normalized['mp_payment_id'];
        $payment->save();

        return $normalized;
    }

    public function refreshPaymentStatus(PaymentRequest $payment, object $config): array
    {
        $pixMeta = $this->getPixMeta($payment);
        $mpPaymentId = $pixMeta['mp_payment_id'] ?? $payment->transaction_id;

        if (empty($mpPaymentId)) {
            return ['status' => 'failed', 'mp_payment_id' => null];
        }

        $mpPayment = $this->fetchPayment((string) $mpPaymentId, $config);
        if (!$mpPayment) {
            return ['status' => 'failed', 'mp_payment_id' => $mpPaymentId];
        }

        $normalized = $this->normalizePixPayload($mpPayment);
        $this->persistPixMeta($payment, $normalized);

        if ($normalized['status'] === 'paid' && !$payment->is_paid) {
            $this->markAsPaid($payment, (string) $normalized['mp_payment_id']);
        }

        return $normalized;
    }

    public function processWebhookPayload(array $payload, object $config): void
    {
        $mpPaymentId = $payload['data']['id'] ?? $payload['id'] ?? null;
        if (empty($mpPaymentId)) {
            return;
        }

        $mpPayment = $this->fetchPayment((string) $mpPaymentId, $config);
        if (!$mpPayment) {
            return;
        }

        $payment = PaymentRequest::query()
            ->where('id', $mpPayment['external_reference'] ?? '')
            ->orWhere('transaction_id', (string) $mpPaymentId)
            ->first();

        if (!$payment) {
            $this->audit(null, 'webhook_orphan', ['mp_payment_id' => $mpPaymentId, 'payload' => $payload]);
            return;
        }

        $normalized = $this->normalizePixPayload($mpPayment);
        $this->audit($payment->id, 'webhook', [
            'mp_payment_id' => $mpPaymentId,
            'status' => $normalized['status'],
            'payload' => $payload,
        ]);
        $this->persistPixMeta($payment, $normalized);

        if ($normalized['status'] === 'paid' && !$payment->is_paid) {
            $this->markAsPaid($payment, (string) $mpPaymentId);
        }
    }

    public function verifyWebhookSignature(?string $signatureHeader, ?string $requestId, string $dataId, ?string $secret): bool
    {
        if (empty($secret) || empty($signatureHeader) || empty($requestId)) {
            return true;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $segment) {
            [$key, $value] = array_pad(explode('=', trim($segment), 2), 2, null);
            if ($key && $value) {
                $parts[trim($key)] = trim($value);
            }
        }

        $ts = $parts['ts'] ?? '';
        $signature = $parts['v1'] ?? '';
        if ($ts === '' || $signature === '') {
            return false;
        }

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $signature);
    }

    private function fetchPayment(string $mpPaymentId, object $config): ?array
    {
        $response = Http::withToken($config->access_token)
            ->get(self::API_BASE . '/v1/payments/' . $mpPaymentId);

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    private function normalizePixPayload(array $mpPayment): array
    {
        $transactionData = $mpPayment['point_of_interaction']['transaction_data'] ?? [];

        return [
            'mp_payment_id' => $mpPayment['id'] ?? null,
            'status' => $this->mapStatus($mpPayment),
            'qr_code' => $transactionData['qr_code'] ?? null,
            'qr_code_base64' => $transactionData['qr_code_base64'] ?? null,
            'ticket_url' => $transactionData['ticket_url'] ?? null,
            'expires_at' => $mpPayment['date_of_expiration'] ?? null,
            'amount' => $mpPayment['transaction_amount'] ?? null,
        ];
    }

    private function mapStatus(array $mpPayment): string
    {
        $status = $mpPayment['status'] ?? 'pending';
        $expiresAt = $mpPayment['date_of_expiration'] ?? null;

        if ($expiresAt && in_array($status, ['pending', 'in_process'], true)) {
            if (now()->greaterThan($expiresAt)) {
                return 'expired';
            }
        }

        return match ($status) {
            'approved' => 'paid',
            'rejected', 'cancelled' => 'failed',
            default => 'pending',
        };
    }

    private function getPixMeta(PaymentRequest $payment): array
    {
        $additional = json_decode($payment->additional_data, true) ?? [];

        return $additional['pix'] ?? [];
    }

    private function persistPixMeta(PaymentRequest $payment, array $pixData): void
    {
        $additional = json_decode($payment->additional_data, true) ?? [];
        $additional['pix'] = array_merge($additional['pix'] ?? [], $pixData);
        $payment->additional_data = json_encode($additional);
        $payment->save();
    }

    private function markAsPaid(PaymentRequest $payment, string $mpPaymentId): void
    {
        if ($payment->is_paid) {
            return;
        }

        $payment->update([
            'payment_method' => 'mercadopago_pix',
            'is_paid' => 1,
            'transaction_id' => $mpPaymentId,
        ]);

        $this->audit($payment->id, 'paid', ['mp_payment_id' => $mpPaymentId]);

        if (function_exists($payment->hook)) {
            call_user_func($payment->hook, $payment);
        }
    }

    private function audit(?string $paymentRequestId, string $event, array $payload): void
    {
        MercadoPagoPixLog::query()->create([
            'payment_request_id' => $paymentRequestId,
            'event' => $event,
            'payload' => $payload,
        ]);
    }
}
