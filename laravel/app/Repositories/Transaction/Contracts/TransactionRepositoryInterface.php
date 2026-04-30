<?php

namespace App\Repositories\Transaction\Contracts;

use App\Models\Transaction;
use App\Repositories\Transaction\DTO\CreateTransactionInput;

interface TransactionRepositoryInterface
{
    public function create(CreateTransactionInput $input): Transaction;

    public function findById(int $transactionId): Transaction;

    public function markAsProcessing(Transaction $transaction): void;

    public function markAsApproved(Transaction $transaction, ?int $gatewayId = null): void;

    public function markAsRejected(Transaction $transaction, array $errors): void;

    public function markAsError(Transaction $transaction, array $errors): void;
}
