<?php

namespace Modules\FinanceManagement\Service;

use Illuminate\Support\Facades\DB;
use Modules\FinanceManagement\Entities\PaymentSplit;
use Modules\FinanceManagement\Entities\WalletTransaction;
use Modules\FinanceManagement\Service\Interfaces\DriverSubscriptionServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinancialSplitServiceInterface;
use Modules\TripManagement\Entities\TripRequest;

class FinancialSplitService implements FinancialSplitServiceInterface
{
    public function __construct(
        private readonly FinanceSettingServiceInterface $financeSettingService,
        private readonly DriverWalletService $driverWalletService,
        private readonly DriverSubscriptionServiceInterface $driverSubscriptionService,
        private readonly FinanceAuditService $financeAuditService,
    ) {
    }

    public function processFromTrip(
        TripRequest $trip,
        ?string $paymentId = null,
        float $gatewayFee = 0,
        bool $creditWallet = true,
    ): ?PaymentSplit {
        if (PaymentSplit::query()
            ->where('ride_id', $trip->id)
            ->where('status', 'confirmed')
            ->exists()) {
            return null;
        }

        return $this->processRidePayment([
            'payment_id' => $paymentId,
            'ride_id' => $trip->id,
            'driver_id' => $trip->driver_id,
            'gross_amount' => (float) $trip->paid_fare,
            'gateway_fee' => $gatewayFee,
            'credit_wallet' => $creditWallet,
            'payment_method' => $trip->payment_method,
            'trip' => $trip,
        ]);
    }

    public function processRidePayment(array $payload): PaymentSplit
    {
        $settings = $this->financeSettingService->get();
        $gross = (float) ($payload['gross_amount'] ?? 0);
        $gatewayFee = (float) ($payload['gateway_fee'] ?? 0);
        $driverId = $payload['driver_id'];
        $net = max(0, $gross - $gatewayFee);
        $creditWallet = (bool) ($payload['credit_wallet'] ?? true);
        /** @var TripRequest|null $trip */
        $trip = $payload['trip'] ?? null;

        $amounts = $this->resolveSplitAmounts($driverId, $settings, $net, $trip);
        $commissionPercent = $amounts['commission_percent'];
        $adminAmount = $amounts['admin_amount'];
        $driverAmount = $amounts['driver_amount'];

        return DB::transaction(function () use ($payload, $gross, $gatewayFee, $net, $driverId, $commissionPercent, $adminAmount, $driverAmount, $settings, $creditWallet) {
            $split = PaymentSplit::query()->create([
                'payment_id' => $payload['payment_id'] ?? null,
                'ride_id' => $payload['ride_id'] ?? null,
                'order_id' => $payload['order_id'] ?? null,
                'driver_id' => $driverId,
                'gross_amount' => $gross,
                'admin_amount' => $adminAmount,
                'driver_amount' => $driverAmount,
                'gateway_fee' => $gatewayFee,
                'net_amount' => $net,
                'commission_percent' => $commissionPercent,
                'status' => 'confirmed',
            ]);

            $wallet = $this->driverWalletService->ensureWallet($driverId);

            if ($creditWallet && $driverAmount > 0) {
                $releaseDays = (int) $settings->balance_release_days;

                if ($releaseDays > 0) {
                    $wallet->pending_balance += $driverAmount;
                } else {
                    $wallet->available_balance += $driverAmount;
                }
                $wallet->total_received += $driverAmount;
                $wallet->save();

                $walletTransaction = WalletTransaction::query()->create([
                    'driver_id' => $driverId,
                    'wallet_id' => $wallet->id,
                    'ride_id' => $payload['ride_id'] ?? null,
                    'order_id' => $payload['order_id'] ?? null,
                    'type' => WalletTransaction::TYPE_CREDIT,
                    'amount' => $driverAmount,
                    'description' => $commissionPercent > 0
                        ? "Crédito corrida (comissão {$commissionPercent}%)"
                        : 'Crédito corrida (plano ativo)',
                    'status' => $releaseDays > 0 ? 'pending' : 'completed',
                    'reference' => $split->id,
                ]);

                $this->financeAuditService->logWalletTransaction($walletTransaction);
            } elseif (!$creditWallet && $driverAmount > 0) {
                $wallet->total_received += $driverAmount;
                $wallet->save();
            }

            if ($adminAmount > 0 && $creditWallet) {
                $commissionTransaction = WalletTransaction::query()->create([
                    'driver_id' => $driverId,
                    'wallet_id' => $wallet->id,
                    'ride_id' => $payload['ride_id'] ?? null,
                    'type' => WalletTransaction::TYPE_COMMISSION,
                    'amount' => $adminAmount,
                    'description' => 'Comissão administrativa',
                    'status' => 'completed',
                    'reference' => $split->id,
                ]);

                $this->financeAuditService->logWalletTransaction($commissionTransaction, 'wallet_commission_recorded');
            }

            $this->financeAuditService->log(
                action: 'payment_split_confirmed',
                entityType: PaymentSplit::class,
                entityId: $split->id,
                after: array_merge($split->toArray(), [
                    'credit_wallet' => $creditWallet,
                    'payment_method' => $payload['payment_method'] ?? null,
                ]),
                notes: 'Split ride ' . ($payload['ride_id'] ?? 'n/a'),
            );

            return $split;
        });
    }

    /**
     * @return array{commission_percent: float, admin_amount: float, driver_amount: float}
     */
    private function resolveSplitAmounts(
        string $driverId,
        $settings,
        float $net,
        ?TripRequest $trip = null,
    ): array {
        if ($this->driverSubscriptionService->hasActivePlan($driverId)) {
            return [
                'commission_percent' => 0,
                'admin_amount' => 0,
                'driver_amount' => round($net, 2),
            ];
        }

        if ($trip) {
            $trip->loadMissing('fee');
            if ($trip->fee && $net > 0) {
                $adminAmount = min((float) $trip->fee->admin_commission, $net);

                return [
                    'commission_percent' => round(($adminAmount / $net) * 100, 2),
                    'admin_amount' => round($adminAmount, 2),
                    'driver_amount' => round($net - $adminAmount, 2),
                ];
            }
        }

        $commissionPercent = (float) $settings->default_commission_percent;
        $adminAmount = round($net * ($commissionPercent / 100), 2);

        return [
            'commission_percent' => $commissionPercent,
            'admin_amount' => $adminAmount,
            'driver_amount' => round($net - $adminAmount, 2),
        ];
    }
}
