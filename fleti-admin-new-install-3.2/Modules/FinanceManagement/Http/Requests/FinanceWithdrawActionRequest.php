<?php

namespace Modules\FinanceManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceWithdrawActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([APPROVED, DENIED, SETTLED])],
            'approval_note' => 'nullable|string|max:1500',
            'denied_note' => 'required_if:status,' . DENIED . '|nullable|string|max:1500',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
