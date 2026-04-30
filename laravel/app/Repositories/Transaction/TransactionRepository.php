<?php

namespace App\Repositories\Transaction;

use App\Enums\PaymentStatus;
use App\Models\Transaction;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use App\Repositories\Transaction\DTO\CreateTransactionInput;

final class TransactionRepository implements TransactionRepositoryInterface
{
    public function findById(int $transactionId): Transaction
    {
        return Transaction::with('fiscalData')->findOrFail($transactionId);
    }

    public function create(CreateTransactionInput $input): Transaction
    {
        return Transaction::create($this->mapInputToAttributes($input));
    }

    /**
     * @return array{0: Transaction, 1: bool}
     */
    public function firstOrCreateByIdempotencyKey(CreateTransactionInput $input): array
    {
        $existing = Transaction::query()
            ->where('idempotency_key', $input->idempotencyKey)
            ->first();

        if ($existing !== null) {
            return [$existing, false];
        }

        return [$this->create($input), true];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapInputToAttributes(CreateTransactionInput $input): array
    {
        return [
            'user_id'                   => $input->userId,
            'product_id'                => $input->productId,
            'payment_amount'            => $input->paymentAmount,
            'payment_method'            => $input->paymentMethod,
            'payment_status'            => $input->paymentStatus,
            'payment_date'              => null,
            'idempotency_key'           => $input->idempotencyKey,
            'transaction_uuid'          => $input->transactionUuid,
            'gateway_id'                => $input->gatewayId,
            'last_4_digits_card_number' => $input->last4DigitsCardNumber,
            'card_brand'                => $input->cardBrand,
            'quantity'                  => $input->quantity,
        ];
    }

    public function markAsProcessing(Transaction $transaction): void
    {
        $transaction->update([
            'payment_status' => PaymentStatus::PROCESSING,
            'dispatched_at'  => now(),
        ]);
    }

    public function markAsApproved(Transaction $transaction, ?int $gatewayId = null): void
    {
        $transaction->update([
            'payment_status' => PaymentStatus::APPROVED,
            'payment_date'   => now(),
            'processed_at'   => now(),
            'gateway_id'     => $gatewayId,
            'failure_reason' => null,
            'error_code'     => null,
        ]);
    }

    public function markAsRejected(Transaction $transaction, array $errors): void
    {
        $transaction->update([
            'payment_status' => PaymentStatus::REJECTED,
            'payment_date'   => null,
            'processed_at'   => now(),
            'failed_at'      => now(),
            'failure_reason' => $errors['error_message'] ?? null,
            'error_code'     => $errors['error_code'] ?? null,
        ]);
    }

    public function markAsError(Transaction $transaction, array $errors): void
    {
        $transaction->update([
            'payment_status' => PaymentStatus::ERROR,
            'payment_date'   => null,
            'processed_at'   => now(),
            'failed_at'      => now(),
            'failure_reason' => $errors['error_message'] ?? null,
            'error_code'     => $errors['error_code'] ?? null,
        ]);
    }
}
