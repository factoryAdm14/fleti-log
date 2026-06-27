<?php

namespace Modules\FinanceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Modules\UserManagement\Entities\User;

class DriverSubscription extends Model
{
    use HasUuid;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'driver_id',
        'plan_id',
        'payment_id',
        'status',
        'starts_at',
        'expires_at',
        'renewal_type',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(DriverPlan::class, 'plan_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at
            && $this->expires_at->isFuture();
    }
}
