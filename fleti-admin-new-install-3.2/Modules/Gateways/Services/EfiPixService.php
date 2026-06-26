<?php

namespace Modules\Gateways\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Gateways\Entities\EfiPixLog;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\Gateways\Traits\Processor;

class EfiPixService
{
    use Processor;

    private const SANDBOX_BASE = 'https://pix-h.api.efipay.com.br';
    private const LIVE_BASE = 'https://pix.api.efipay.com.br';

    public function resolveConfig(): ?object
    {
        $config = $this->paymentConfig('efi_pix', PAYMENT_CONFIG);
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
        $config = $this->paymentConfig('efi_pix', PAYMENT_CONFIG);

        return !is_null($config) && (int) $config->is_active === 1;
    }

    public function createOrGetPixPayment(PaymentRequest $payment, object $config): array
    {
        $pixMeta = $this->getPixMeta($payment);
        $txid = $pixMeta['txid'] ?? $this->buildTxid($payment->id);

        if (!empty($pixMeta['txid'])) {
            $existing = $this->fetchCob($txid, $config);
            if ($existing) {
                $normalized = $this->normalizeFromCob($existing, $config, $pixMeta);
                $this->persistPixMeta($payment, $normalized);

                return $normalized;
            }
        }

        $amount = number_format((float) $payment->payment_amount, 2, '.', '');
        $token = $this->getAccessToken($config);

        $response = $this->httpClient($config)
            ->withToken($token)
            ->put('/v2/cob/' . $txid, [
                'calendario' => ['expiracao' => 3600],
                'valor' => ['original' => $amount],
                'chave' => $config->pix_key,
                'solicitacaoPagador' => 'Fleti ' . substr($payment->id, 0, 8),
            ]);

        $this->audit($payment->id, 'create_cob', [
            'http_status' => $response->status(),
            'txid' => $txid,
            'response' => $response->json(),
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'EFI PIX: ' . ($response->json('mensagem') ?? $response->body())
            );
        }

        $cob = $response->json();
        $normalized = $this->normalizeFromCob($cob, $config);
        $normalized['txid'] = $txid;
        $this->persistPixMeta($payment, $normalized);
        $payment->transaction_id = $txid;
        $payment->save();

        return $normalized;
    }

    public function refreshPaymentStatus(PaymentRequest $payment, object $config): array
    {
        $pixMeta = $this->getPixMeta($payment);
        $txid = $pixMeta['txid'] ?? $payment->transaction_id;

        if (empty($txid)) {
            return ['status' => 'failed', 'txid' => null];
        }

        $cob = $this->fetchCob((string) $txid, $config);
        if (!$cob) {
            return ['status' => 'failed', 'txid' => $txid];
        }

        $normalized = $this->normalizeFromCob($cob, $config, $pixMeta);
        $normalized['txid'] = $txid;
        $this->persistPixMeta($payment, $normalized);

        if ($normalized['status'] === 'paid' && !$payment->is_paid) {
            $this->markAsPaid($payment, (string) $txid);
        }

        return $normalized;
    }

    public function processWebhookPayload(array $payload, object $config): void
    {
        $pixItems = $payload['pix'] ?? [];
        if (!is_array($pixItems)) {
            return;
        }

        foreach ($pixItems as $pix) {
            $txid = $pix['txid'] ?? null;
            if (empty($txid)) {
                continue;
            }

            $payment = PaymentRequest::query()
                ->where('transaction_id', $txid)
                ->first();

            if (!$payment) {
                $payment = PaymentRequest::query()
                    ->where('additional_data', 'like', '%"txid":"' . $txid . '"%')
                    ->first();
            }

            if (!$payment) {
                $this->audit(null, 'webhook_orphan', ['txid' => $txid, 'payload' => $pix]);
                continue;
            }

            $this->audit($payment->id, 'webhook', ['txid' => $txid, 'payload' => $pix]);

            $cob = $this->fetchCob((string) $txid, $config);
            if ($cob) {
                $normalized = $this->normalizeFromCob($cob, $config, $this->getPixMeta($payment));
                $normalized['txid'] = $txid;
                $this->persistPixMeta($payment, $normalized);

                if ($normalized['status'] === 'paid' && !$payment->is_paid) {
                    $this->markAsPaid($payment, (string) $txid);
                }
            }
        }
    }

    public function certificatePath(object $config): ?string
    {
        if (empty($config->certificate_file)) {
            return null;
        }

        $path = storage_path('app/private/payment_modules/efi_pix/' . $config->certificate_file);

        return file_exists($path) ? $path : null;
    }

    private function getAccessToken(object $config): string
    {
        $cacheKey = 'efi_pix_token_' . md5(($config->client_id ?? '') . ($config->mode ?? 'test'));

        return Cache::remember($cacheKey, 3500, function () use ($config) {
            $response = $this->httpClient($config)
                ->withBasicAuth($config->client_id, $config->client_secret)
                ->post('/oauth/token', ['grant_type' => 'client_credentials']);

            if (!$response->successful()) {
                throw new \RuntimeException('EFI PIX auth: ' . ($response->json('error_description') ?? $response->body()));
            }

            return $response->json('access_token');
        });
    }

    private function fetchCob(string $txid, object $config): ?array
    {
        try {
            $token = $this->getAccessToken($config);
        } catch (\Throwable) {
            return null;
        }

        $response = $this->httpClient($config)
            ->withToken($token)
            ->get('/v2/cob/' . $txid);

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }

    private function fetchQrCode(int $locId, object $config): array
    {
        $token = $this->getAccessToken($config);

        $response = $this->httpClient($config)
            ->withToken($token)
            ->get('/v2/loc/' . $locId . '/qrcode');

        if (!$response->successful()) {
            return ['qr_code' => null, 'qr_code_base64' => null];
        }

        $data = $response->json();
        $image = $data['imagemQrcode'] ?? '';
        if (str_starts_with($image, 'data:image')) {
            $image = preg_replace('#^data:image/[^;]+;base64,#', '', $image);
        }

        return [
            'qr_code' => $data['qrcode'] ?? null,
            'qr_code_base64' => $image ?: null,
        ];
    }

    private function normalizeFromCob(array $cob, object $config, array $existingMeta = []): array
    {
        $locId = $cob['loc']['id'] ?? ($existingMeta['loc_id'] ?? null);
        $qr = ['qr_code' => $existingMeta['qr_code'] ?? null, 'qr_code_base64' => $existingMeta['qr_code_base64'] ?? null];

        if ($locId && (empty($qr['qr_code']) || empty($qr['qr_code_base64']))) {
            $qr = $this->fetchQrCode((int) $locId, $config);
        }

        $expiresAt = null;
        if (!empty($cob['calendario']['criacao']) && !empty($cob['calendario']['expiracao'])) {
            $expiresAt = date('c', strtotime($cob['calendario']['criacao']) + (int) $cob['calendario']['expiracao']);
        }

        return [
            'txid' => $cob['txid'] ?? ($existingMeta['txid'] ?? null),
            'loc_id' => $locId,
            'status' => $this->mapStatus($cob['status'] ?? 'ATIVA', $expiresAt),
            'qr_code' => $qr['qr_code'],
            'qr_code_base64' => $qr['qr_code_base64'],
            'expires_at' => $expiresAt,
            'amount' => $cob['valor']['original'] ?? null,
        ];
    }

    private function mapStatus(string $efiStatus, ?string $expiresAt): string
    {
        if ($expiresAt && in_array($efiStatus, ['ATIVA'], true) && now()->greaterThan($expiresAt)) {
            return 'expired';
        }

        return match ($efiStatus) {
            'CONCLUIDA' => 'paid',
            'REMOVIDA_PELO_USUARIO_RECEBEDOR', 'REMOVIDA_PELO_PSP' => 'expired',
            default => 'pending',
        };
    }

    private function buildTxid(string $paymentId): string
    {
        return substr('f' . str_replace('-', '', $paymentId), 0, 35);
    }

    private function httpClient(object $config): PendingRequest
    {
        $baseUrl = ($config->mode ?? 'test') === 'live' ? self::LIVE_BASE : self::SANDBOX_BASE;
        $pending = Http::baseUrl($baseUrl)->acceptJson()->asJson();

        $certPath = $this->certificatePath($config);
        if ($certPath) {
            $pending = $pending->withOptions([
                'curl' => [
                    CURLOPT_SSLCERT => $certPath,
                    CURLOPT_SSLCERTPASSWD => $config->certificate_password ?? '',
                    CURLOPT_SSLCERTTYPE => 'P12',
                ],
            ]);
        }

        return $pending;
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

    private function markAsPaid(PaymentRequest $payment, string $txid): void
    {
        if ($payment->is_paid) {
            return;
        }

        $payment->update([
            'payment_method' => 'efi_pix',
            'is_paid' => 1,
            'transaction_id' => $txid,
        ]);

        $this->audit($payment->id, 'paid', ['txid' => $txid]);

        if (function_exists($payment->hook)) {
            call_user_func($payment->hook, $payment);
        }
    }

    private function audit(?string $paymentRequestId, string $event, array $payload): void
    {
        EfiPixLog::query()->create([
            'payment_request_id' => $paymentRequestId,
            'event' => $event,
            'payload' => $payload,
        ]);
    }
}
