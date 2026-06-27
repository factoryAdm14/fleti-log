<?php

namespace Modules\FinanceManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id' => 'required|uuid|exists:users,id',
            'plan_id' => 'required|uuid|exists:driver_plans,id',
        ];
    }
}
