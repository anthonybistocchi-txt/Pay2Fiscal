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
        'transaction_id',
        'gateway_id',
        'last_4_digits_card_number',
        'quantity',
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
}
