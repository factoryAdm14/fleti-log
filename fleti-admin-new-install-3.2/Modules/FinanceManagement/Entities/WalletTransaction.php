<?php

namespace Modules\FinanceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasUuid;

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_WITHDRAW = 'withdraw';
    public const TYPE_REFUND = 'refund';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'driver_id',
        'wallet_id',
        'ride_id',
        'order_id',
        'type',
        'amount',
        'description',
        'status',
        'reference',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function wallet()
    {
        return $this->belongsTo(DriverWallet::class, 'wallet_id');
    }
}
