<?php

namespace Modules\FinanceManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\FinanceManagement\Lib\PaymentGatewayResolver;
use Modules\FinanceManagement\Service\PaymentGatewayManager;

class PaymentGatewayConfigController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {
    }

    public function available(): JsonResponse
    {
        $methods = PaymentGatewayResolver::availableDigitalMethods();
        $gateways = $this->gatewayManager->activePixGateways()
            ->map(fn ($gateway) => [
                'key' => $gateway->key(),
                'name' => $gateway->displayName(),
                'supports_pix' => $gateway->supportsPix(),
                'supports_card' => $gateway->supportsCard(),
                'is_active' => $gateway->isActive(),
            ])
            ->values();

        return response()->json(responseFormatter(DEFAULT_200, [
            'primary_pix' => PaymentGatewayResolver::resolvePixGatewayKey(),
            'primary_card' => PaymentGatewayResolver::resolveCardGatewayKey(),
            'digital_methods' => $methods,
            'gateways' => $gateways,
        ]));
    }
}
