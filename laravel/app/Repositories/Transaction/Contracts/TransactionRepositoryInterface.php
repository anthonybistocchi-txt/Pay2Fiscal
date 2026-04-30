<?php

namespace App\Repositories\Transaction\Contracts;

use App\Models\Transaction;
use App\Repositories\Transaction\DTO\CreateTransactionInput;

interface TransactionRepositoryInterface
{
    public function create(CreateTransactionInput $input): Transaction;

    /**
     * Idempotent variant of {@see create()}. Returns the existing transaction
     * for the given idempotency key when present; otherwise persists a new one.
     *
     * The second tuple element is true when a new row was inserted.
     *
     * @return array{0: Transaction, 1: bool}
     */
    public function firstOrCreateByIdempotencyKey(CreateTransactionInput $input): array;

    public function findById(int $transactionId): Transaction;

    public function markAsProcessing(Transaction $transaction): void;

    public function markAsApproved(Transaction $transaction, ?int $gatewayId = null): void;

    public function markAsRejected(Transaction $transaction, array $errors): void;

    public function markAsError(Transaction $transaction, array $errors): void;
}
