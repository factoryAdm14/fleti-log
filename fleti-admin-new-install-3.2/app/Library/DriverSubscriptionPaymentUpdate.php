<?php

use Modules\FinanceManagement\Service\FinancePaymentVerificationService;
use Modules\FinanceManagement\Service\Interfaces\DriverSubscriptionServiceInterface;

if (!function_exists('driverSubscriptionPaymentUpdate')) {
    function driverSubscriptionPaymentUpdate($data)
    {
        if (!app(FinancePaymentVerificationService::class)->assertPayment($data)) {
            return null;
        }

        return app(DriverSubscriptionServiceInterface::class)->activateFromPayment($data);
    }
}
