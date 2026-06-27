<?php

namespace Modules\FinanceManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinanceWithdrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()->user_type === 'driver';
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'withdraw_method' => 'required|integer|exists:withdraw_methods,id',
            'withdraw_method_info_id' => 'nullable|uuid|exists:user_withdraw_method_infos,id',
            'note' => 'nullable|string|max:500',
        ];
    }
}
