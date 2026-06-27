<?php

namespace Modules\ChattingManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserManagement\Entities\User;

class ChannelServiceRequest extends Model
{
    use HasUuid;

    protected $fillable = [
        'channel_id',
        'channel_conversation_id',
        'customer_id',
        'service_type',
        'origin_address',
        'origin_lat',
        'origin_lng',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'notes',
    ];

    protected $casts = [
        'origin_lat' => 'float',
        'origin_lng' => 'float',
        'destination_lat' => 'float',
        'destination_lng' => 'float',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChannelList::class, 'channel_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChannelConversation::class, 'channel_conversation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
