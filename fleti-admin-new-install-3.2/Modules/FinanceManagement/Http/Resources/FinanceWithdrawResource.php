<?php

namespace Modules\FinanceManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceWithdrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'source' => $this->source,
            'method' => $this->whenLoaded('method', fn () => [
                'id' => $this->method?->id,
                'method_name' => $this->method?->method_name,
            ]),
            'method_fields' => $this->method_fields,
            'driver_note' => $this->driver_note,
            'approval_note' => $this->approval_note,
            'denied_note' => $this->denied_note,
            'receipt_url' => $this->receipt_url,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
