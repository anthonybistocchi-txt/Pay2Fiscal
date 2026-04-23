<?php

namespace App\Repositories\Purchase;

use App\Models\Transaction;
use App\Repositories\Purchase\Contract\TransactionRepositoryInterface;
use App\Repositories\Purchase\DTO\CreateTransactionInput;

final class TransactionRepository implements TransactionRepositoryInterface
{
    public function findById(int $transactionId): Transaction
    {
        return Transaction::query()->findOrFail($transactionId);
    }

    public function updatePaymentStatus(int $transactionId, string $paymentStatus): void
    {
        Transaction::query()->whereKey($transactionId)->update([
            'payment_status' => $paymentStatus,
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
            'transaction_id'            => $input->transactionId,
            'gateway_id'                => $input->gatewayId,
            'last_4_digits_card_number' => $input->last4DigitsCardNumber,
            'card_brand'                => $input->cardBrand,
            'quantity'                  => $input->quantity,
        ]);
    }
}
