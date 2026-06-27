<?php

namespace Tests\Unit\Finance;

use Illuminate\Support\Str;
use Modules\FinanceManagement\Entities\PaymentSplit;
use Modules\FinanceManagement\Service\FinanceDashboardService;
use Modules\Gateways\Entities\PaymentRequest;
use Tests\Support\FinanceTestCase;

class FinanceDashboardServiceTest extends FinanceTestCase
{
    public function test_dashboard_aggregates_revenue_and_withdraw_metrics(): void
    {
        $driverId = (string) Str::uuid();

        PaymentSplit::query()->create([
            'ride_id' => (string) Str::uuid(),
            'driver_id' => $driverId,
            'gross_amount' => 100,
            'admin_amount' => 15,
            'driver_amount' => 85,
            'gateway_fee' => 2,
            'net_amount' => 98,
            'commission_percent' => 15,
            'status' => 'confirmed',
        ]);

        PaymentRequest::query()->create([
            'payer_id' => $driverId,
            'payment_amount' => 59.90,
            'payment_method' => 'mercadopago_pix',
            'attribute' => 'driver_subscription',
            'is_paid' => true,
        ]);

        PaymentRequest::query()->create([
            'payer_id' => (string) Str::uuid(),
            'payment_amount' => 30,
            'payment_method' => 'mercadopago',
            'attribute' => 'order',
            'is_paid' => true,
        ]);

        $stats = app(FinanceDashboardService::class)->getStats('all');

        $this->assertSame(159.9, $stats['total_revenue']);
        $this->assertSame(15.0, $stats['commission_revenue']);
        $this->assertSame(59.9, $stats['plan_revenue']);
        $this->assertSame(59.9, $stats['pix_received']);
        $this->assertSame(30.0, $stats['card_received']);
        $this->assertSame(72.9, $stats['estimated_net_profit']);
    }
}
