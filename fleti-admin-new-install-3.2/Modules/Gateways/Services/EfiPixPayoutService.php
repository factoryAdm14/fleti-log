<?php

namespace Modules\Gateways\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Gateways\Traits\Processor;

class EfiPixPayoutService
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

    /**
     * @return array{success: bool, end_to_end_id?: string|null, reference?: string, status?: string, message?: string}
     */
    public function sendPix(string $idEnvio, float $amount, string $destinationPixKey, ?string $info = null): array
    {
        $config = $this->resolveConfig();
        if (!$config || empty($config->pix_key)) {
            return ['success' => false, 'message' => 'EFI PIX não configurado.'];
        }

        if (!$this->certificatePath($config)) {
            return ['success' => false, 'message' => 'Certificado EFI não encontrado.'];
        }

        $token = $this->getAccessToken($config);
        $payload = [
            'valor' => number_format($amount, 2, '.', ''),
            'pagador' => [
                'chave' => $config->pix_key,
                'infoPagador' => $info ?? 'Saque Fleti',
            ],
            'favorecido' => [
                'chave' => $destinationPixKey,
            ],
        ];

        $response = $this->httpClient($config)
            ->withToken($token)
            ->put('/v2/gn/pix/' . $idEnvio, $payload);

        $body = $response->json() ?? [];

        if (!$response->successful()) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => $body['mensagem'] ?? $body['detail'] ?? $response->body(),
                'reference' => $idEnvio,
            ];
        }

        return [
            'success' => true,
            'status' => $body['status'] ?? 'sent',
            'end_to_end_id' => $body['e2eId'] ?? $body['endToEndId'] ?? null,
            'reference' => $idEnvio,
        ];
    }

    public function certificatePath(object $config): ?string
    {
        if (empty($config->certificate_file)) {
            return null;
        }

        $path = storage_path('app/private/payment_modules/efi_pix/' . $config->certificate_file);

        return file_exists($path) ? $path : null;
    }

    public function buildIdEnvio(int|string $withdrawId): string
    {
        return 'fletiwd' . substr(preg_replace('/\D+/', '', (string) $withdrawId), 0, 27);
    }

    private function getAccessToken(object $config): string
    {
        $cacheKey = 'efi_pix_payout_token_' . md5(($config->client_id ?? '') . ($config->mode ?? 'test'));

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
}
