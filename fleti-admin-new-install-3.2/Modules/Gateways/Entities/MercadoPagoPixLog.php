<?php

namespace Modules\Gateways\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Gateways\Traits\HasUuid;

class MercadoPagoPixLog extends Model
{
    use HasUuid;

    protected $table = 'mercadopago_pix_logs';

    protected $fillable = [
        'payment_request_id',
        'event',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
