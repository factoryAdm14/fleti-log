<?php

namespace Modules\FinanceManagement\Service\Interfaces;

use Modules\FinanceManagement\Entities\PaymentSplit;
use Modules\TripManagement\Entities\TripRequest;

interface FinancialSplitServiceInterface
{
    /**
     * Process payment split after confirmed ride/delivery payment.
     *
     * @param  array{ride_id?:string,payment_id?:string,driver_id:string,gross_amount:float,gateway_fee?:float,credit_wallet?:bool}  $payload
     */
    public function processRidePayment(array $payload): PaymentSplit;

    /**
     * Build and process split from a paid trip request.
     */
    public function processFromTrip(
        TripRequest $trip,
        ?string $paymentId = null,
        float $gatewayFee = 0,
        bool $creditWallet = true,
    ): ?PaymentSplit;
}
