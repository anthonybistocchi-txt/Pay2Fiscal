<?php

namespace App\Repositories\Transaction;

use App\Models\Transaction;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use App\Repositories\Transaction\DTO\CreateTransactionInput;

final class TransactionRepository implements TransactionRepositoryInterface
{
    private const APPROVED_PAYMENT_STATUS = 'APPROVED';
    private const ERROR_PAYMENT_STATUS = 'ERROR';
    private const PROCESSING_PAYMENT_STATUS = 'PROCESSING';
    // private const REJECTED_PAYMENT_STATUS = 'REJECTED';
    // private const REFUNDED_PAYMENT_STATUS = 'REFUNDED';

    public function findById(int $transactionId): Transaction
    {
        return Transaction::query()->findOrFail($transactionId);
    }

    public function markAsApproved(int $transactionId): void
    {
        Transaction::query()->whereKey($transactionId)->update([
            'payment_status'   => self::APPROVED_PAYMENT_STATUS,
            'payment_date'     => now(),
            'dispatched_at'    => now(),
            'failed_at'        => null,
            'go_response_code' => null,
            'go_request_id'    => null,
            'failure_reason'   => null,
            'processed_at'     => now(),
        ]);
    }

    public function markAsError(int $transactionId, array $goErrors): void
    {
        Transaction::query()->whereKey($transactionId)->update([
            'payment_status'   => self::ERROR_PAYMENT_STATUS,
            'payment_date'     => null,
            'dispatched_at'    => now(),
            'failed_at'        => now(),
            'failure_reason'   => $goErrors['failure_reason'],
            'go_response_code' => $goErrors['go_response_code'],
            'go_request_id'    => $goErrors['go_request_id'],
            'processed_at'     => now(),
        ]);
    }

    public function markAsProcessing(int $transactionId): void
    {
        Transaction::query()->whereKey($transactionId)->update([
            'payment_status'   => self::PROCESSING_PAYMENT_STATUS,
            'payment_date'     => null,
            'dispatched_at'    => now(),
            'failed_at'        => null,
            'go_response_code' => null,
            'go_request_id'    => null,
            'failure_reason'   => null,
            'processed_at'     => null,
        ]);
    }

    public function create(CreateTransactionInput $input): Transaction
    {
        return Transaction::create([
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
        ]);
    }
}
