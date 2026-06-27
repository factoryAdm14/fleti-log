<?php

namespace Modules\FinanceManagement\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\FinanceManagement\Contracts\PaymentGatewayInterface;
use Modules\FinanceManagement\DTO\WebhookProcessingResult;
use Modules\FinanceManagement\Gateways\EfiPixGateway;
use Modules\FinanceManagement\Gateways\MercadoPagoCardGateway;
use Modules\FinanceManagement\Gateways\MercadoPagoPixGateway;
use Modules\FinanceManagement\Lib\PaymentGatewayResolver;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways = [];

    public function __construct(
        MercadoPagoPixGateway $mercadoPagoPixGateway,
        EfiPixGateway $efiPixGateway,
        MercadoPagoCardGateway $mercadoPagoCardGateway,
        private readonly FinanceSettingServiceInterface $financeSettingService,
        private readonly FinanceAuditService $financeAuditService,
    ) {
        foreach ([$mercadoPagoPixGateway, $efiPixGateway, $mercadoPagoCardGateway] as $gateway) {
            $this->gateways[$gateway->key()] = $gateway;
        }
    }

    public function get(string $key): ?PaymentGatewayInterface
    {
        return $this->gateways[$key] ?? null;
    }

    /**
     * @return Collection<int, PaymentGatewayInterface>
     */
    public function all(): Collection
    {
        return collect($this->gateways);
    }

    /**
     * @return Collection<int, PaymentGatewayInterface>
     */
    public function activePixGateways(): Collection
    {
        $settings = $this->financeSettingService->get();

        return $this->all()
            ->filter(fn (PaymentGatewayInterface $gateway) => $gateway->supportsPix() && $gateway->isActive())
            ->filter(function (PaymentGatewayInterface $gateway) use ($settings) {
                if (!$settings->pix_payment_enabled) {
                    return false;
                }

                return PaymentGatewayResolver::isPixGatewayAllowed($gateway->key(), $settings);
            })
            ->values();
    }

    public function primaryPixGateway(): ?PaymentGatewayInterface
    {
        $key = PaymentGatewayResolver::resolvePixGatewayKey();

        return $key ? $this->get($key) : null;
    }

    public function detectGatewayFromWebhook(Request $request): ?string
    {
        if ($request->has('pix') || $request->hasHeader('x-efi-signature')) {
            return 'efi_pix';
        }

        if ($request->has('data.id') || $request->has('type') || $request->hasHeader('x-signature')) {
            return 'mercadopago_pix';
        }

        $gateway = $request->route('gateway');
        if (is_string($gateway) && isset($this->gateways[$gateway])) {
            return $gateway;
        }

        return PaymentGatewayResolver::resolvePixGatewayKey();
    }

    public function handlePixWebhook(Request $request, ?string $gatewayKey = null): WebhookProcessingResult
    {
        $gatewayKey ??= $this->detectGatewayFromWebhook($request);
        $gateway = $gatewayKey ? $this->get($gatewayKey) : null;

        if (!$gateway || !$gateway->supportsPix()) {
            return WebhookProcessingResult::rejected($gatewayKey ?? 'unknown', 'Gateway PIX não encontrado');
        }

        if (!$gateway->isActive()) {
            return WebhookProcessingResult::rejected($gateway->key(), 'Gateway inativo');
        }

        $settings = $this->financeSettingService->get();
        $signatureRequired = (bool) ($settings->webhook_signature_required ?? true);

        if ($signatureRequired && !$gateway->verifyWebhook($request)) {
            $this->financeAuditService->log(
                action: 'webhook_rejected',
                entityType: 'payment_gateway',
                entityId: $gateway->key(),
                after: ['reason' => 'invalid_signature', 'ip' => $request->ip()],
            );

            return WebhookProcessingResult::rejected($gateway->key(), 'Assinatura inválida');
        }

        $result = $gateway->processWebhook($request->all(), $request);

        $this->financeAuditService->log(
            action: $result->accepted ? 'webhook_processed' : 'webhook_failed',
            entityType: 'payment_gateway',
            entityId: $gateway->key(),
            after: [
                'payment_request_id' => $result->paymentRequestId,
                'status' => $result->status,
                'message' => $result->message,
            ],
        );

        return $result;
    }
}
