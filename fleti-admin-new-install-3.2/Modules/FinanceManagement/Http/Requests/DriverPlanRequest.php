<?php

namespace Modules\FinanceManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:2000',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1|max:3650',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'benefits' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
