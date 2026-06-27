<?php

namespace Modules\FinanceManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'payment_id' => $this->when($this->status === 'pending', $this->payment_id),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'days_remaining' => $this->expires_at?->isFuture()
                ? (int) now()->diffInDays($this->expires_at, false)
                : 0,
            'is_active' => $this->isActive(),
            'plan' => DriverPlanResource::make($this->whenLoaded('plan')),
        ];
    }
}
