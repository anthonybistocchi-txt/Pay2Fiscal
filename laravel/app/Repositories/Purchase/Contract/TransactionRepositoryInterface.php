<?php

namespace App\Repositories\Purchase\Contract;

use App\Models\Transaction;
use App\Repositories\Purchase\DTO\CreateTransactionInput;

interface TransactionRepositoryInterface
{
    public function create(CreateTransactionInput $input): Transaction;
}
