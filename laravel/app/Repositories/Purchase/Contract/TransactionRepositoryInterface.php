<?php

namespace App\Repositories\Purchase\Contract;

use App\Models\Transaction;
use App\Repositories\Purchase\DTO\CreateTransactionInput;

interface TransactionRepositoryInterface
{
    public function create(CreateTransactionInput $input): Transaction;

    public function findById(int $transactionId): Transaction;

    public function updatePaymentStatusSuccess(int $transactionId): void;

    public function updatePaymentStatusFailed(int $transactionId, array $goErrors): void;
}
