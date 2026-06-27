<?php

namespace Modules\FinanceManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\FinanceManagement\Entities\DriverPlan;
use Modules\FinanceManagement\Entities\FinanceSetting;

class FinanceManagementDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!FinanceSetting::query()->exists()) {
            FinanceSetting::query()->create([
                'id' => (string) Str::uuid(),
                'commission_mode_enabled' => true,
                'subscription_mode_enabled' => true,
                'hybrid_mode_enabled' => true,
                'active_mode' => 'hybrid',
                'default_commission_percent' => 15,
                'min_withdraw_amount' => 50,
                'balance_release_days' => 0,
                'manual_withdraw_approval' => true,
                'pix_payment_enabled' => true,
                'card_payment_enabled' => true,
                'primary_gateway' => 'mercadopago',
                'plan_expiry_rule' => 'revert_commission',
                'plan_grace_period_days' => 3,
                'auto_pix_payout_enabled' => false,
                'withdraw_security_enabled' => true,
                'max_withdraw_amount' => 0,
                'max_withdraw_requests_per_day' => 3,
                'max_withdraw_amount_per_day' => 0,
                'webhook_signature_required' => true,
                'payment_amount_tolerance_percent' => 1,
            ]);
        }

        $plans = [
            ['name' => 'Plano Mensal Livre', 'duration_days' => 30, 'price' => 59.90],
            ['name' => 'Plano Trimestral Livre', 'duration_days' => 90, 'price' => 149.90],
            ['name' => 'Plano Semestral Livre', 'duration_days' => 180, 'price' => 279.90],
            ['name' => 'Plano Anual Livre', 'duration_days' => 365, 'price' => 599.90],
        ];

        foreach ($plans as $plan) {
            DriverPlan::query()->firstOrCreate(
                ['name' => $plan['name']],
                [
                    'description' => 'Trabalhe sem comissão por corrida',
                    'price' => $plan['price'],
                    'duration_days' => $plan['duration_days'],
                    'commission_percentage' => 0,
                    'benefits' => ['zero_commission', 'priority_support'],
                    'is_active' => true,
                ]
            );
        }
    }
}
