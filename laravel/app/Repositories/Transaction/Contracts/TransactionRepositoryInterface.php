<?php

namespace App\Repositories\Transaction\Contracts;

use App\Models\Transaction;
use App\Repositories\Transaction\DTO\CreateTransactionInput;

interface TransactionRepositoryInterface
{
    public function create(CreateTransactionInput $input): Transaction;

    public function findById(int $transactionId): Transaction;

    public function markAsApproved(int $transactionId): void;

    public function markAsError(int $transactionId, array $fiscalErrors): void;

    public function markAsProcessing(int $transactionId): void;
}
