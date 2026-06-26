<?php

namespace Modules\TripManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripStop extends Model
{
    use HasUuid;

    protected $fillable = [
        'trip_request_id',
        'stop_order',
        'type',
        'address',
        'latitude',
        'longitude',
        'status',
        'arrived_at',
        'completed_at',
        'proof_photo',
        'signature',
        'qr_code',
        'notes',
    ];

    protected $casts = [
        'stop_order' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'arrived_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tripRequest(): BelongsTo
    {
        return $this->belongsTo(TripRequest::class, 'trip_request_id');
    }
}
