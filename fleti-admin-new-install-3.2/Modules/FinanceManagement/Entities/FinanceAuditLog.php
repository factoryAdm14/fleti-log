<?php

namespace Modules\FinanceManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class FinanceAuditLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'user_id',
        'user_type',
        'ip_address',
        'before',
        'after',
        'notes',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];
}
