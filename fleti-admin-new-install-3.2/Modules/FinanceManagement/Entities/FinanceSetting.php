<?php

namespace Modules\FinanceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class FinanceSetting extends Model
{
    use HasUuid;

    protected $fillable = [
        'commission_mode_enabled',
        'subscription_mode_enabled',
        'hybrid_mode_enabled',
        'active_mode',
        'default_commission_percent',
        'min_withdraw_amount',
        'balance_release_days',
        'manual_withdraw_approval',
        'pix_payment_enabled',
        'card_payment_enabled',
        'primary_gateway',
        'plan_expiry_rule',
        'plan_grace_period_days',
        'auto_pix_payout_enabled',
        'withdraw_security_enabled',
        'max_withdraw_amount',
        'max_withdraw_requests_per_day',
        'max_withdraw_amount_per_day',
        'webhook_signature_required',
        'payment_amount_tolerance_percent',
    ];

    protected $casts = [
        'commission_mode_enabled' => 'boolean',
        'subscription_mode_enabled' => 'boolean',
        'hybrid_mode_enabled' => 'boolean',
        'default_commission_percent' => 'float',
        'min_withdraw_amount' => 'float',
        'balance_release_days' => 'integer',
        'manual_withdraw_approval' => 'boolean',
        'pix_payment_enabled' => 'boolean',
        'card_payment_enabled' => 'boolean',
        'plan_grace_period_days' => 'integer',
        'auto_pix_payout_enabled' => 'boolean',
        'withdraw_security_enabled' => 'boolean',
        'max_withdraw_amount' => 'float',
        'max_withdraw_requests_per_day' => 'integer',
        'max_withdraw_amount_per_day' => 'float',
        'webhook_signature_required' => 'boolean',
        'payment_amount_tolerance_percent' => 'float',
    ];
}
