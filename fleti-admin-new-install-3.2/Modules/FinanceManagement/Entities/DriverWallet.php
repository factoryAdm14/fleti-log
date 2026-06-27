<?php

namespace Modules\FinanceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserManagement\Entities\User;

class DriverWallet extends Model
{
    use HasUuid;

    protected $fillable = [
        'driver_id',
        'available_balance',
        'pending_balance',
        'blocked_balance',
        'total_received',
        'total_withdrawn',
    ];

    protected $casts = [
        'available_balance' => 'float',
        'pending_balance' => 'float',
        'blocked_balance' => 'float',
        'total_received' => 'float',
        'total_withdrawn' => 'float',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }
}
