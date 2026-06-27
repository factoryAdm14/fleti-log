<?php

namespace Modules\FinanceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class FinancePixPayoutLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'withdraw_request_id',
        'gateway',
        'event',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
