<?php

namespace Modules\UserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class DriverWalletStoreOrUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'driver_id' => 'required',
            'amount' => 'required|numeric|gt:0',
            'reference' => 'max:900',
        ];
    }

    public function authorize()
    {
        return Auth::check();
    }
}
