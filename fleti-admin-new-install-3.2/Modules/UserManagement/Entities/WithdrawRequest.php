<?php

namespace Modules\UserManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WithdrawRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'method_id',
        'method_fields',
        'note',
        'driver_note',
        'approval_note',
        'denied_note',
        'rejection_cause',
        'is_approved',
        'status',
        'source',
        'wallet_transaction_id',
        'pix_end_to_end_id',
        'pix_payout_gateway',
        'pix_payout_status',
        'pix_payout_reference',
        'receipt_url',
        'paid_at',
        'admin_id',
        'under_review_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'method_fields' => 'json',
        'amount' => 'double',
        'is_approved' => 'boolean',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function method(){
        return $this->belongsTo(WithdrawMethod::class, 'method_id');
    }
    protected static function newFactory()
    {
        return \Modules\UserManagement\Database\factories\WithdrawRequestFactory::new();
    }
}
