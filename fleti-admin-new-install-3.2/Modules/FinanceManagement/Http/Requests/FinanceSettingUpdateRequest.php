<?php

namespace Modules\FinanceManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'commission_mode_enabled' => 'nullable|boolean',
            'subscription_mode_enabled' => 'nullable|boolean',
            'hybrid_mode_enabled' => 'nullable|boolean',
            'active_mode' => ['required', Rule::in(['commission', 'subscription', 'hybrid'])],
            'default_commission_percent' => 'required|numeric|min:0|max:100',
            'min_withdraw_amount' => 'required|numeric|min:0',
            'balance_release_days' => 'required|integer|min:0|max:365',
            'manual_withdraw_approval' => 'nullable|boolean',
            'pix_payment_enabled' => 'nullable|boolean',
            'card_payment_enabled' => 'nullable|boolean',
            'primary_gateway' => ['required', Rule::in(['mercadopago', 'efi'])],
            'plan_expiry_rule' => ['required', Rule::in(['revert_commission', 'block_rides', 'grace_period'])],
            'plan_grace_period_days' => 'required|integer|min:0|max:90',
            'auto_pix_payout_enabled' => 'nullable|boolean',
            'withdraw_security_enabled' => 'nullable|boolean',
            'max_withdraw_amount' => 'required|numeric|min:0',
            'max_withdraw_requests_per_day' => 'required|integer|min:0|max:100',
            'max_withdraw_amount_per_day' => 'required|numeric|min:0',
            'webhook_signature_required' => 'nullable|boolean',
            'payment_amount_tolerance_percent' => 'required|numeric|min:0|max:10',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'commission_mode_enabled',
            'subscription_mode_enabled',
            'hybrid_mode_enabled',
            'manual_withdraw_approval',
            'pix_payment_enabled',
            'card_payment_enabled',
            'auto_pix_payout_enabled',
            'withdraw_security_enabled',
            'webhook_signature_required',
        ] as $field) {
            $this->merge([
                $field => $this->boolean($field),
            ]);
        }
    }
}
