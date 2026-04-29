<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'payment_amount',
        'payment_method',
        'payment_status',
        'payment_date',
        'card_brand',
        'idempotency_key',
        'transaction_uuid',
        'gateway_id',
        'last_4_digits_card_number',
        'quantity',
        'dispatched_at',
        'processed_at',
        'failed_at',
        'failure_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function fiscalData()
    {
        return $this->hasOne(TransactionFiscalData::class);
    }
}
