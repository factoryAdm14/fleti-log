<?php

namespace Modules\Gateways\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Gateways\Traits\HasUuid;
use Modules\UserManagement\Entities\User;

class PaymentRequest extends Model
{
    use HasUuid;
    use HasFactory;

    protected $table = 'payment_requests';

    protected $fillable = [
        'payer_id',
        'receiver_id',
        'payment_amount',
        'gateway_callback_url',
        'hook',
        'transaction_id',
        'currency_code',
        'payment_method',
        'additional_data',
        'is_paid',
        'payer_information',
        'external_redirect_link',
        'receiver_information',
        'attribute_id',
        'attribute',
        'payment_platform',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'additional_data' => 'array',
        'is_paid' => 'boolean',
        'payment_amount' => 'float',
    ];

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }
}
