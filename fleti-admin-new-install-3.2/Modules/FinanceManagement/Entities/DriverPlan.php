<?php

namespace Modules\FinanceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverPlan extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'commission_percentage',
        'benefits',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'duration_days' => 'integer',
        'commission_percentage' => 'float',
        'benefits' => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(DriverSubscription::class, 'plan_id');
    }
}
