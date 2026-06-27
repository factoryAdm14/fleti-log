<?php

namespace Tests\Unit\Finance;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\FinanceManagement\Entities\DriverPlan;
use Modules\FinanceManagement\Entities\DriverSubscription;
use Modules\FinanceManagement\Entities\FinanceAuditLog;
use Modules\FinanceManagement\Service\DriverSubscriptionService;
use Modules\FinanceManagement\Service\FinancePaymentVerificationService;
use Modules\Gateways\Entities\PaymentRequest;
use Tests\Support\FinanceTestCase;

class DriverSubscriptionServiceTest extends FinanceTestCase
{
    private string $driverId;

    private DriverPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driverId = (string) Str::uuid();
        $this->seedDriverUser($this->driverId);
        $this->plan = DriverPlan::query()->create([
            'name' => 'Mensal Teste',
            'price' => 59.90,
            'duration_days' => 30,
            'commission_percentage' => 0,
            'is_active' => true,
        ]);
    }

    public function test_create_pending_checkout_for_pix_plan_purchase(): void
    {
        $subscription = app(DriverSubscriptionService::class)->createPendingCheckout(
            $this->driverId,
            $this->plan->id,
        );

        $this->assertSame(DriverSubscription::STATUS_PENDING, $subscription->status);
        $this->assertEquals((string) $this->plan->id, (string) $subscription->plan_id);
    }

    public function test_activate_subscription_from_pix_payment(): void
    {
        $subscription = DriverSubscription::query()->create([
            'driver_id' => $this->driverId,
            'plan_id' => $this->plan->id,
            'status' => DriverSubscription::STATUS_PENDING,
            'renewal_type' => 'auto',
        ]);

        $payment = PaymentRequest::query()->create([
            'payer_id' => $this->driverId,
            'payment_amount' => 59.90,
            'payment_method' => 'mercadopago_pix',
            'hook' => 'driverSubscriptionPaymentUpdate',
            'attribute' => 'driver_subscription',
            'attribute_id' => $subscription->id,
            'is_paid' => true,
        ]);

        $this->assertTrue(app(FinancePaymentVerificationService::class)->assertPayment($payment));

        $activated = app(DriverSubscriptionService::class)->activateFromPayment($payment);

        $this->assertSame(DriverSubscription::STATUS_ACTIVE, $activated->status);
        $this->assertNotNull($activated->expires_at);
        $this->assertTrue($activated->expires_at->isFuture());
    }

    public function test_activate_subscription_from_card_payment(): void
    {
        $subscription = DriverSubscription::query()->create([
            'driver_id' => $this->driverId,
            'plan_id' => $this->plan->id,
            'status' => DriverSubscription::STATUS_PENDING,
            'renewal_type' => 'auto',
        ]);

        $payment = PaymentRequest::query()->create([
            'payer_id' => $this->driverId,
            'payment_amount' => 59.90,
            'payment_method' => 'mercadopago',
            'attribute' => 'driver_subscription',
            'attribute_id' => $subscription->id,
            'is_paid' => true,
        ]);

        $activated = app(DriverSubscriptionService::class)->activateFromPayment($payment);

        $this->assertSame(DriverSubscription::STATUS_ACTIVE, $activated->status);
    }

    public function test_renewal_extends_from_current_expiry(): void
    {
        $currentExpiry = now()->addDays(10);

        DriverSubscription::query()->create([
            'driver_id' => $this->driverId,
            'plan_id' => $this->plan->id,
            'status' => DriverSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(20),
            'expires_at' => $currentExpiry,
            'renewal_type' => 'auto',
        ]);

        $pending = DriverSubscription::query()->create([
            'driver_id' => $this->driverId,
            'plan_id' => $this->plan->id,
            'status' => DriverSubscription::STATUS_PENDING,
            'renewal_type' => 'auto',
        ]);

        $payment = PaymentRequest::query()->create([
            'payer_id' => $this->driverId,
            'payment_amount' => 59.90,
            'payment_method' => 'efi_pix',
            'attribute' => 'driver_subscription',
            'attribute_id' => $pending->id,
            'is_paid' => true,
        ]);

        $activated = app(DriverSubscriptionService::class)->activateFromPayment($payment);

        $this->assertTrue($activated->expires_at->greaterThan($currentExpiry));
        $this->assertSame(
            $currentExpiry->copy()->addDays(30)->toDateString(),
            $activated->expires_at->toDateString(),
        );
    }

    public function test_payment_amount_mismatch_marks_subscription_failed(): void
    {
        $subscription = DriverSubscription::query()->create([
            'driver_id' => $this->driverId,
            'plan_id' => $this->plan->id,
            'status' => DriverSubscription::STATUS_PENDING,
            'renewal_type' => 'auto',
        ]);

        $payment = PaymentRequest::query()->create([
            'payer_id' => $this->driverId,
            'payment_amount' => 10,
            'payment_method' => 'mercadopago_pix',
            'attribute' => 'driver_subscription',
            'attribute_id' => $subscription->id,
            'is_paid' => true,
        ]);

        $this->assertFalse(app(FinancePaymentVerificationService::class)->assertPayment($payment));

        app(DriverSubscriptionService::class)->activateFromPayment($payment);

        $subscription->refresh();
        $this->assertSame(DriverSubscription::STATUS_FAILED, $subscription->status);
    }

    public function test_activate_from_payment_is_idempotent(): void
    {
        $subscription = DriverSubscription::query()->create([
            'driver_id' => $this->driverId,
            'plan_id' => $this->plan->id,
            'status' => DriverSubscription::STATUS_PENDING,
            'renewal_type' => 'auto',
        ]);

        $payment = PaymentRequest::query()->create([
            'payer_id' => $this->driverId,
            'payment_amount' => 59.90,
            'payment_method' => 'mercadopago_pix',
            'attribute' => 'driver_subscription',
            'attribute_id' => $subscription->id,
            'is_paid' => true,
        ]);

        $service = app(DriverSubscriptionService::class);
        $first = $service->activateFromPayment($payment);
        $second = $service->activateFromPayment($payment->fresh());

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DriverSubscription::query()->where('driver_id', $this->driverId)->where('status', 'active')->count());
    }

    public function test_expired_subscription_is_not_active(): void
    {
        DriverSubscription::query()->create([
            'driver_id' => $this->driverId,
            'plan_id' => $this->plan->id,
            'status' => DriverSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subDays(40),
            'expires_at' => now()->subDay(),
            'renewal_type' => 'auto',
        ]);

        $this->assertNull(app(DriverSubscriptionService::class)->getActiveSubscription($this->driverId));
    }

    public function test_duplicate_webhook_logs_system_audit_entry(): void
    {
        app(\Modules\FinanceManagement\Service\FinanceAuditService::class)->logSystem(
            action: 'payment_webhook_duplicate',
            entityType: PaymentRequest::class,
            entityId: (string) Str::uuid(),
            after: ['gateway' => 'mercadopago_pix'],
        );

        $this->assertTrue(
            FinanceAuditLog::query()->where('action', 'payment_webhook_duplicate')->exists()
        );
    }
}
