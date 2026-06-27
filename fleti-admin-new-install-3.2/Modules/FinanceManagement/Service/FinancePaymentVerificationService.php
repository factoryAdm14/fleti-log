<?php

namespace Modules\FinanceManagement\Service;

use Modules\FinanceManagement\Entities\DriverSubscription;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\TripManagement\Entities\TripRequest;

class FinancePaymentVerificationService
{
    public function __construct(
        private readonly FinanceSettingServiceInterface $financeSettingService,
        private readonly FinanceAuditService $financeAuditService,
    ) {
    }

    public function assertPayment(PaymentRequest $payment): bool
    {
        $expectedAmount = $this->resolveExpectedAmount($payment);

        if ($expectedAmount === null) {
            return true;
        }

        $tolerance = $this->toleranceRatio();
        $minimumAccepted = round($expectedAmount * (1 - $tolerance), 2);

        if ((float) $payment->payment_amount + 0.0001 < $minimumAccepted) {
            $this->financeAuditService->logSystem(
                action: 'payment_amount_mismatch',
                entityType: PaymentRequest::class,
                entityId: (string) $payment->id,
                after: [
                    'expected_amount' => $expectedAmount,
                    'received_amount' => (float) $payment->payment_amount,
                    'attribute' => $payment->attribute,
                    'attribute_id' => $payment->attribute_id,
                    'payment_method' => $payment->payment_method,
                ],
                notes: 'Pagamento rejeitado por divergência de valor',
            );

            return false;
        }

        return true;
    }

    private function resolveExpectedAmount(PaymentRequest $payment): ?float
    {
        return match ($payment->attribute) {
            'driver_subscription' => $this->expectedSubscriptionAmount($payment),
            'order' => $this->expectedTripAmount($payment),
            default => null,
        };
    }

    private function expectedSubscriptionAmount(PaymentRequest $payment): ?float
    {
        $subscription = DriverSubscription::query()
            ->with('plan')
            ->find($payment->attribute_id);

        return $subscription?->plan?->price !== null
            ? (float) $subscription->plan->price
            : null;
    }

    private function expectedTripAmount(PaymentRequest $payment): ?float
    {
        $trip = TripRequest::query()->find($payment->attribute_id);

        if (!$trip) {
            return null;
        }

        return round((float) $trip->paid_fare + (float) ($trip->tips ?? 0), 2);
    }

    private function toleranceRatio(): float
    {
        $settings = $this->financeSettingService->get();
        $percent = (float) ($settings->payment_amount_tolerance_percent ?? 1);

        return max(0, min($percent, 10)) / 100;
    }
}
