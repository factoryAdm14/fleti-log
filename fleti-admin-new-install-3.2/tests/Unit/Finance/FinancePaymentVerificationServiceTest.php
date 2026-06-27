<?php

namespace Tests\Unit\Finance;

use Illuminate\Support\Str;
use Modules\FinanceManagement\Entities\DriverPlan;
use Modules\FinanceManagement\Entities\DriverSubscription;
use Modules\FinanceManagement\Service\FinancePaymentVerificationService;
use Modules\Gateways\Entities\PaymentRequest;
use Tests\Support\FinanceTestCase;

class FinancePaymentVerificationServiceTest extends FinanceTestCase
{
    public function test_accepts_subscription_payment_within_tolerance(): void
    {
        $plan = DriverPlan::query()->create([
            'name' => 'Plano',
            'price' => 100,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $subscription = DriverSubscription::query()->create([
            'driver_id' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'status' => DriverSubscription::STATUS_PENDING,
        ]);

        $payment = PaymentRequest::query()->create([
            'payment_amount' => 99.5,
            'payment_method' => 'mercadopago_pix',
            'attribute' => 'driver_subscription',
            'attribute_id' => $subscription->id,
            'is_paid' => true,
        ]);

        $this->assertTrue(app(FinancePaymentVerificationService::class)->assertPayment($payment));
    }

    public function test_rejects_subscription_payment_below_tolerance(): void
    {
        $plan = DriverPlan::query()->create([
            'name' => 'Plano',
            'price' => 100,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $subscription = DriverSubscription::query()->create([
            'driver_id' => (string) Str::uuid(),
            'plan_id' => $plan->id,
            'status' => DriverSubscription::STATUS_PENDING,
        ]);

        $payment = PaymentRequest::query()->create([
            'payment_amount' => 90,
            'payment_method' => 'mercadopago_pix',
            'attribute' => 'driver_subscription',
            'attribute_id' => $subscription->id,
            'is_paid' => true,
        ]);

        $this->assertFalse(app(FinancePaymentVerificationService::class)->assertPayment($payment));
    }
}
