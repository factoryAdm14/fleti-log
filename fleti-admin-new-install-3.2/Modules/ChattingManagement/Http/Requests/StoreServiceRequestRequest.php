<?php

namespace Modules\ChattingManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_id' => 'required|uuid',
            'service_type' => 'required|string|in:ride,delivery',
            'origin_address' => 'required|string|max:500',
            'origin_lat' => 'nullable|numeric',
            'origin_lng' => 'nullable|numeric',
            'destination_address' => 'required|string|max:500',
            'destination_lat' => 'nullable|numeric',
            'destination_lng' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
