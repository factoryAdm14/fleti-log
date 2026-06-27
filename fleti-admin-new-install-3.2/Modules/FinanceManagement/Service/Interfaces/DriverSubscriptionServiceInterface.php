<?php

namespace Modules\FinanceManagement\Service\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\FinanceManagement\Entities\DriverSubscription;
use Modules\Gateways\Entities\PaymentRequest;

interface DriverSubscriptionServiceInterface
{
    public function hasActivePlan(string $driverId): bool;

    public function getActiveSubscription(string $driverId): ?DriverSubscription;

    public function getPendingSubscription(string $driverId): ?DriverSubscription;

    public function createPendingCheckout(string $driverId, string $planId): DriverSubscription;

    public function attachPaymentId(string $subscriptionId, string $paymentId): void;

    public function activateFromPayment(PaymentRequest $payment): ?DriverSubscription;

    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function activateManually(string $driverId, string $planId, string $adminId): DriverSubscription;

    public function cancel(string $subscriptionId, string $adminId, ?string $reason = null): DriverSubscription;
}
