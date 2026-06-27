<?php

namespace App\Events;

use App\Models\Transaction;

class TransactionApproved
{
    public function __construct(
        public readonly Transaction $transaction,
    ) {}
    
    

}
