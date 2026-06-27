<?php

namespace Modules\FinanceManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\FinanceManagement\Lib\PaymentGatewayResolver;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;

class FinancePlanCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()->user_type === 'driver';
    }

    public function rules(): array
    {
        $methods = PaymentGatewayResolver::availableDigitalMethods(
            app(FinanceSettingServiceInterface::class)->get()
        );

        return [
            'payment_method' => ['required', 'string', Rule::in($methods)],
        ];
    }
}
