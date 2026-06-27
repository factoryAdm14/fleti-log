<?php

namespace Tests\Unit\Finance;

use Illuminate\Support\Str;
use Modules\FinanceManagement\Entities\DriverSubscription;
use Modules\FinanceManagement\Entities\DriverWallet;
use Modules\FinanceManagement\Entities\PaymentSplit;
use Modules\FinanceManagement\Service\FinancialSplitService;
use Tests\Support\FinanceTestCase;

class FinancialSplitServiceTest extends FinanceTestCase
{
    public function test_applies_default_commission_for_driver_without_plan(): void
    {
        $driverId = (string) Str::uuid();

        $split = app(FinancialSplitService::class)->processRidePayment([
            'ride_id' => (string) Str::uuid(),
            'driver_id' => $driverId,
            'gross_amount' => 100,
            'gateway_fee' => 0,
            'credit_wallet' => true,
            'payment_method' => 'mercadopago_pix',
        ]);

        $this->assertSame(15.0, (float) $split->commission_percent);
        $this->assertSame(15.0, (float) $split->admin_amount);
        $this->assertSame(85.0, (float) $split->driver_amount);

        $wallet = DriverWallet::query()->where('driver_id', $driverId)->first();
        $this->assertSame(85.0, (float) $wallet->available_balance);
    }

    public function test_driver_with_active_plan_has_zero_commission(): void
    {
        $driverId = (string) Str::uuid();
        $planId = (string) Str::uuid();

        \Modules\FinanceManagement\Entities\DriverPlan::query()->create([
            'id' => $planId,
            'name' => 'Mensal',
            'price' => 59.90,
            'duration_days' => 30,
            'commission_percentage' => 0,
            'is_active' => true,
        ]);

        DriverSubscription::query()->create([
            'driver_id' => $driverId,
            'plan_id' => $planId,
            'status' => DriverSubscription::STATUS_ACTIVE,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDays(20),
            'renewal_type' => 'auto',
        ]);

        $split = app(FinancialSplitService::class)->processRidePayment([
            'ride_id' => (string) Str::uuid(),
            'driver_id' => $driverId,
            'gross_amount' => 100,
            'gateway_fee' => 2,
            'credit_wallet' => true,
            'payment_method' => 'mercadopago_pix',
        ]);

        $this->assertSame(0.0, (float) $split->admin_amount);
        $this->assertSame(98.0, (float) $split->driver_amount);
        $this->assertSame(2.0, (float) $split->gateway_fee);
    }

    public function test_pix_ride_payment_deducts_gateway_fee_from_net(): void
    {
        $driverId = (string) Str::uuid();

        $split = app(FinancialSplitService::class)->processRidePayment([
            'ride_id' => (string) Str::uuid(),
            'driver_id' => $driverId,
            'gross_amount' => 50,
            'gateway_fee' => 1.5,
            'credit_wallet' => true,
            'payment_method' => 'mercadopago_pix',
        ]);

        $this->assertSame(48.5, (float) $split->net_amount);
        $this->assertEqualsWithDelta(7.28, (float) $split->admin_amount, 0.02);
        $this->assertEqualsWithDelta(41.22, (float) $split->driver_amount, 0.01);
    }

    public function test_cash_ride_does_not_credit_wallet_but_records_split(): void
    {
        $driverId = (string) Str::uuid();

        $split = app(FinancialSplitService::class)->processRidePayment([
            'ride_id' => (string) Str::uuid(),
            'driver_id' => $driverId,
            'gross_amount' => 80,
            'gateway_fee' => 0,
            'credit_wallet' => false,
            'payment_method' => 'cash',
        ]);

        $wallet = DriverWallet::query()->where('driver_id', $driverId)->first();
        $this->assertSame(0.0, (float) $wallet->available_balance);
        $this->assertSame(68.0, (float) $wallet->total_received);
        $this->assertSame('confirmed', $split->status);
    }

    public function test_process_from_trip_is_idempotent_for_confirmed_split(): void
    {
        $driverId = (string) Str::uuid();
        $rideId = (string) Str::uuid();
        $service = app(FinancialSplitService::class);

        $trip = new \Modules\TripManagement\Entities\TripRequest([
            'paid_fare' => 40,
            'driver_id' => $driverId,
            'payment_method' => 'mercadopago_pix',
        ]);
        $trip->id = $rideId;

        $first = $service->processFromTrip($trip, paymentId: (string) Str::uuid(), gatewayFee: 0, creditWallet: true);
        $second = $service->processFromTrip($trip, paymentId: (string) Str::uuid(), gatewayFee: 0, creditWallet: true);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(1, PaymentSplit::query()->where('ride_id', $rideId)->count());
    }

    public function test_expired_plan_does_not_zero_commission(): void
    {
        $driverId = (string) Str::uuid();
        $planId = (string) Str::uuid();

        \Modules\FinanceManagement\Entities\DriverPlan::query()->create([
            'id' => $planId,
            'name' => 'Expirado',
            'price' => 59.90,
            'duration_days' => 30,
            'commission_percentage' => 0,
            'is_active' => true,
        ]);

        DriverSubscription::query()->create([
            'driver_id' => $driverId,
            'plan_id' => $planId,
            'status' => DriverSubscription::STATUS_EXPIRED,
            'starts_at' => now()->subDays(60),
            'expires_at' => now()->subDay(),
            'renewal_type' => 'auto',
        ]);

        $this->assertFalse(app(\Modules\FinanceManagement\Service\Interfaces\DriverSubscriptionServiceInterface::class)->hasActivePlan($driverId));

        $split = app(FinancialSplitService::class)->processRidePayment([
            'ride_id' => (string) Str::uuid(),
            'driver_id' => $driverId,
            'gross_amount' => 100,
            'gateway_fee' => 0,
            'credit_wallet' => true,
            'payment_method' => 'mercadopago_pix',
        ]);

        $this->assertGreaterThan(0, (float) $split->admin_amount);
    }
}
