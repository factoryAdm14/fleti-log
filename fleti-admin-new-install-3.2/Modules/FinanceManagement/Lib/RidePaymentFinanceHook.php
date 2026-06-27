<?php

namespace Modules\FinanceManagement\Lib;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\FinanceManagement\Lib\GatewayFeeResolver;
use Modules\FinanceManagement\Service\Interfaces\FinancialSplitServiceInterface;
use Modules\TripManagement\Entities\TripRequest;

/**
 * Bridges legacy trip payment flow with the FinanceManagement split layer.
 * Failures are logged and never block payment completion.
 */
class RidePaymentFinanceHook
{
    public static function handle(
        TripRequest $trip,
        ?string $paymentId = null,
        float $gatewayFee = 0,
    ): void {
        try {
            if (!Schema::hasTable('payment_splits') || !Schema::hasTable('finance_settings')) {
                return;
            }

            if (!$trip->driver_id || $trip->payment_status != PAID) {
                return;
            }

            $trip->loadMissing(['fee', 'driver']);

            $creditWallet = self::shouldCreditDriverWallet($trip->payment_method);
            $gatewayFee = GatewayFeeResolver::fromPaymentRequest($paymentId);

            app(FinancialSplitServiceInterface::class)->processFromTrip(
                trip: $trip,
                paymentId: $paymentId,
                gatewayFee: $gatewayFee,
                creditWallet: $creditWallet,
            );
        } catch (\Throwable $e) {
            Log::error('Finance split hook failed', [
                'trip_id' => $trip->id,
                'payment_method' => $trip->payment_method,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }
    }

    public static function shouldCreditDriverWallet(?string $paymentMethod): bool
    {
        return $paymentMethod !== 'cash';
    }
}
