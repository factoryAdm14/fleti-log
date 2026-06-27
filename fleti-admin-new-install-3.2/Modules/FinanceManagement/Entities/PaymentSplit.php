<?php

namespace Modules\FinanceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PaymentSplit extends Model
{
    use HasUuid;

    protected $fillable = [
        'payment_id',
        'ride_id',
        'order_id',
        'driver_id',
        'gross_amount',
        'admin_amount',
        'driver_amount',
        'gateway_fee',
        'net_amount',
        'commission_percent',
        'status',
    ];

    protected $casts = [
        'gross_amount' => 'float',
        'admin_amount' => 'float',
        'driver_amount' => 'float',
        'gateway_fee' => 'float',
        'net_amount' => 'float',
        'commission_percent' => 'float',
    ];
}
