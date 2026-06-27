<?php

namespace Modules\FinanceManagement\Lib;

use Modules\Gateways\Entities\PaymentRequest;

class GatewayFeeResolver
{
    public static function fromPaymentRequest(?string $paymentRequestId): float
    {
        if (empty($paymentRequestId)) {
            return 0;
        }

        $payment = PaymentRequest::query()->find($paymentRequestId);
        if (!$payment) {
            return 0;
        }

        $additional = is_array($payment->additional_data)
            ? $payment->additional_data
            : (json_decode($payment->additional_data ?? '{}', true) ?? []);

        if (isset($additional['gateway_fee'])) {
            return max(0, (float) $additional['gateway_fee']);
        }

        $pixMeta = $additional['pix'] ?? [];

        return max(0, (float) ($pixMeta['gateway_fee'] ?? 0));
    }
}
