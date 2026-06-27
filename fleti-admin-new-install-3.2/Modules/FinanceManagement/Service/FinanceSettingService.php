<?php

namespace Modules\FinanceManagement\Service;

use Illuminate\Support\Str;
use Modules\FinanceManagement\Entities\FinanceSetting;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;

class FinanceSettingService implements FinanceSettingServiceInterface
{
    public function get(): FinanceSetting
    {
        $settings = FinanceSetting::query()->first();

        if ($settings) {
            return $settings;
        }

        return FinanceSetting::query()->create([
            'id' => (string) Str::uuid(),
            'commission_mode_enabled' => true,
            'subscription_mode_enabled' => false,
            'hybrid_mode_enabled' => false,
            'active_mode' => 'commission',
            'default_commission_percent' => config('financemanagement.default_commission_percent', 15),
            'min_withdraw_amount' => config('financemanagement.min_withdraw_amount', 50),
            'balance_release_days' => config('financemanagement.balance_release_days', 0),
            'manual_withdraw_approval' => true,
            'pix_payment_enabled' => true,
            'card_payment_enabled' => true,
            'primary_gateway' => 'mercadopago',
            'plan_expiry_rule' => 'revert_commission',
            'plan_grace_period_days' => 0,
            'auto_pix_payout_enabled' => false,
            'withdraw_security_enabled' => true,
            'max_withdraw_amount' => 0,
            'max_withdraw_requests_per_day' => 3,
            'max_withdraw_amount_per_day' => 0,
            'webhook_signature_required' => true,
            'payment_amount_tolerance_percent' => 1,
        ]);
    }

    public function update(array $data): FinanceSetting
    {
        $settings = $this->get();
        $settings->update($data);

        return $settings->fresh();
    }
}
