<?php

namespace Modules\FinanceManagement\Service;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\FinanceManagement\Entities\DriverPlan;
use Modules\FinanceManagement\Entities\DriverSubscription;
use Modules\FinanceManagement\Exceptions\FinanceWithdrawException;
use Modules\FinanceManagement\Service\Interfaces\DriverSubscriptionServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\DriverPlanServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\UserManagement\Entities\User;

class DriverSubscriptionService implements DriverSubscriptionServiceInterface
{
    public function __construct(
        private readonly FinanceSettingServiceInterface $financeSettingService,
        private readonly FinanceAuditService $financeAuditService,
        private readonly DriverPlanServiceInterface $driverPlanService,
    ) {
    }

    public function hasActivePlan(string $driverId): bool
    {
        $settings = $this->financeSettingService->get();

        if (!$settings->subscription_mode_enabled && !$settings->hybrid_mode_enabled) {
            return false;
        }

        return DriverSubscription::query()
            ->where('driver_id', $driverId)
            ->where('status', DriverSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }

    public function getActiveSubscription(string $driverId): ?DriverSubscription
    {
        return DriverSubscription::query()
            ->with('plan')
            ->where('driver_id', $driverId)
            ->where('status', DriverSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>', Carbon::now())
            ->orderByDesc('expires_at')
            ->first();
    }

    public function getPendingSubscription(string $driverId): ?DriverSubscription
    {
        return DriverSubscription::query()
            ->with('plan')
            ->where('driver_id', $driverId)
            ->where('status', DriverSubscription::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->first();
    }

    public function createPendingCheckout(string $driverId, string $planId): DriverSubscription
    {
        $settings = $this->financeSettingService->get();

        if (!$settings->subscription_mode_enabled && !$settings->hybrid_mode_enabled) {
            throw new FinanceWithdrawException('subscription_disabled', 'Planos não habilitados.', 403);
        }

        $plan = $this->driverPlanService->find($planId);
        if (!$plan->is_active) {
            throw new FinanceWithdrawException('plan_inactive', 'Plano inativo.', 403);
        }

        return DB::transaction(function () use ($driverId, $plan) {
            DriverSubscription::query()
                ->where('driver_id', $driverId)
                ->where('status', DriverSubscription::STATUS_PENDING)
                ->update(['status' => DriverSubscription::STATUS_CANCELLED]);

            return DriverSubscription::query()->create([
                'driver_id' => $driverId,
                'plan_id' => $plan->id,
                'status' => DriverSubscription::STATUS_PENDING,
                'renewal_type' => 'auto',
            ])->load('plan');
        });
    }

    public function attachPaymentId(string $subscriptionId, string $paymentId): void
    {
        DriverSubscription::query()
            ->where('id', $subscriptionId)
            ->where('status', DriverSubscription::STATUS_PENDING)
            ->update(['payment_id' => $paymentId]);
    }

    public function activateFromPayment(PaymentRequest $payment): ?DriverSubscription
    {
        if ($payment->attribute !== 'driver_subscription' || empty($payment->attribute_id)) {
            return null;
        }

        return DB::transaction(function () use ($payment) {
            $subscription = DriverSubscription::query()
                ->with('plan')
                ->lockForUpdate()
                ->find($payment->attribute_id);

            if (!$subscription) {
                return null;
            }

            if ($subscription->status === DriverSubscription::STATUS_ACTIVE) {
                return $subscription;
            }

            if ($subscription->status !== DriverSubscription::STATUS_PENDING) {
                return null;
            }

            if ($subscription->driver_id !== $payment->payer_id) {
                return null;
            }

            $plan = $subscription->plan;
            if (!$plan || !$plan->is_active) {
                $subscription->update(['status' => DriverSubscription::STATUS_FAILED]);

                return null;
            }

            if ((float) $payment->payment_amount < (float) $plan->price * 0.99) {
                $subscription->update(['status' => DriverSubscription::STATUS_FAILED]);

                return null;
            }

            $active = $this->getActiveSubscription($subscription->driver_id);
            $startsAt = Carbon::now();

            if ($active && $active->id !== $subscription->id && $active->expires_at?->isFuture()) {
                $startsAt = $active->expires_at->copy();
            }

            if ($active && $active->id !== $subscription->id) {
                $active->update(['status' => DriverSubscription::STATUS_EXPIRED]);
            }

            $expiresAt = $startsAt->copy()->addDays($plan->duration_days);
            $before = $subscription->toArray();

            $subscription->update([
                'payment_id' => $payment->id,
                'status' => DriverSubscription::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'renewal_type' => 'auto',
            ]);

            $this->financeAuditService->log(
                action: 'subscription_activated_payment',
                entityType: DriverSubscription::class,
                entityId: $subscription->id,
                before: $before,
                after: $subscription->fresh()->toArray(),
                notes: "Pagamento {$payment->id} via {$payment->payment_method}",
            );

            $this->notifyDriverSubscriptionActivated($subscription->fresh(['plan', 'driver']));

            return $subscription->fresh(['plan']);
        });
    }

    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = DriverSubscription::query()
            ->with(['plan', 'driver'])
            ->orderByDesc('created_at');

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('driver', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function activateManually(string $driverId, string $planId, string $adminId): DriverSubscription
    {
        $driver = User::query()->where('id', $driverId)->where('user_type', 'driver')->first();
        if (!$driver) {
            throw new FinanceWithdrawException('driver_not_found', 'Motorista não encontrado.', 404);
        }

        $plan = $this->driverPlanService->find($planId);
        if (!$plan->is_active) {
            throw new FinanceWithdrawException('plan_inactive', 'Plano inativo.', 403);
        }

        return DB::transaction(function () use ($driverId, $plan, $adminId) {
            $this->expireActiveSubscriptions($driverId);

            $startsAt = Carbon::now();
            $expiresAt = $startsAt->copy()->addDays($plan->duration_days);

            $subscription = DriverSubscription::query()->create([
                'driver_id' => $driverId,
                'plan_id' => $plan->id,
                'status' => DriverSubscription::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'renewal_type' => 'manual',
            ]);

            $this->financeAuditService->log(
                action: 'subscription_activated_manual',
                entityType: DriverSubscription::class,
                entityId: $subscription->id,
                after: $subscription->toArray(),
                notes: "Ativado pelo admin {$adminId}",
            );

            return $subscription->load('plan');
        });
    }

    public function cancel(string $subscriptionId, string $adminId, ?string $reason = null): DriverSubscription
    {
        $subscription = DriverSubscription::query()->findOrFail($subscriptionId);

        if ($subscription->status === DriverSubscription::STATUS_CANCELLED) {
            return $subscription;
        }

        $subscription->update([
            'status' => DriverSubscription::STATUS_CANCELLED,
        ]);

        $this->financeAuditService->log(
            action: 'subscription_cancelled',
            entityType: DriverSubscription::class,
            entityId: $subscription->id,
            after: $subscription->toArray(),
            notes: $reason ?? "Cancelado pelo admin {$adminId}",
        );

        return $subscription->fresh(['plan', 'driver']);
    }

    private function expireActiveSubscriptions(string $driverId): void
    {
        DriverSubscription::query()
            ->where('driver_id', $driverId)
            ->where('status', DriverSubscription::STATUS_ACTIVE)
            ->update(['status' => DriverSubscription::STATUS_EXPIRED]);
    }

    private function notifyDriverSubscriptionActivated(DriverSubscription $subscription): void
    {
        $driver = $subscription->driver ?? User::query()->find($subscription->driver_id);
        if (!$driver?->fcm_token) {
            return;
        }

        $push = getNotification('admin_message');
        if (empty($push)) {
            return;
        }

        $planName = $subscription->plan?->name ?? '';

        sendDeviceNotification(
            fcm_token: $driver->fcm_token,
            title: translate(key: $push['title'], locale: $driver->current_language_key),
            description: 'Seu plano ' . $planName . ' foi ativado com sucesso.',
            status: $push['status'],
            notification_type: 'driver_subscription',
            action: $push['action'],
            user_id: $driver->id,
        );
    }
}
