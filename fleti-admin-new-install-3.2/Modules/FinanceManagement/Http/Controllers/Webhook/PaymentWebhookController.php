<?php

namespace Modules\FinanceManagement\Http\Controllers\Webhook;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\FinanceManagement\Service\PaymentGatewayManager;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {
    }

    public function pix(Request $request, ?string $gateway = null): JsonResponse
    {
        $result = $this->gatewayManager->handlePixWebhook($request, $gateway);

        if (!$result->accepted) {
            $status = $result->message === 'Assinatura inválida' ? 401 : 422;

            return response()->json([
                'ok' => false,
                'gateway' => $result->gateway,
                'message' => $result->message,
            ], $status);
        }

        return response()->json([
            'ok' => true,
            'gateway' => $result->gateway,
        ]);
    }
}
