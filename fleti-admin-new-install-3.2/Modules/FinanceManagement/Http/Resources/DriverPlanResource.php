<?php

namespace Modules\FinanceManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'duration_days' => $this->duration_days,
            'commission_percentage' => $this->commission_percentage,
            'benefits' => $this->benefits ?? [],
            'is_active' => $this->is_active,
        ];
    }
}
