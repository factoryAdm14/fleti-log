<?php

namespace Tests\Unit;

use Modules\Gateways\Services\MercadoPagoPixService;
use Tests\TestCase;

class MercadoPagoPixServiceTest extends TestCase
{
    public function test_verify_webhook_signature_accepts_valid_hmac(): void
    {
        $service = new MercadoPagoPixService();
        $secret = 'test-secret';
        $requestId = 'req-123';
        $dataId = 'pay-456';
        $ts = '1710000000';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $signature = hash_hmac('sha256', $manifest, $secret);
        $header = "ts={$ts}, v1={$signature}";

        $this->assertTrue(
            $service->verifyWebhookSignature($header, $requestId, $dataId, $secret)
        );
    }

    public function test_verify_webhook_signature_rejects_invalid_hmac(): void
    {
        $service = new MercadoPagoPixService();

        $this->assertFalse(
            $service->verifyWebhookSignature('ts=1, v1=bad', 'req', 'data', 'secret')
        );
    }

    public function test_verify_webhook_signature_allows_missing_secret(): void
    {
        $service = new MercadoPagoPixService();

        $this->assertTrue(
            $service->verifyWebhookSignature(null, null, 'data', null)
        );
    }
}
