<?php

namespace App\Services\Purchase;

use App\DTOs\Purchase\PurchaseStoreData;
use App\DTOs\Purchase\TransactionCreated;
use App\Jobs\DispatchTransactionToFiscalJob;
use App\Repositories\Purchase\Contract\TransactionRepositoryInterface;
use App\Repositories\Purchase\DTO\CreateTransactionInput;
use App\Services\Purchase\Contracts\PurchaseStoreServiceInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Log;

final class PurchaseStoreService implements PurchaseStoreServiceInterface
{
    private const INITIAL_PAYMENT_STATUS = 'PENDING_PAYMENT';

    public function __construct(
        private readonly ConnectionInterface $database,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function handle(PurchaseStoreData $purchasePayload): TransactionCreated
    {
        return $this->database->transaction(function () use ($purchasePayload): TransactionCreated 
        {
            $totalPaymentAmount = $purchasePayload->paymentAmount * $purchasePayload->quantity;

            $persistedTransaction = $this->transactionRepository->create(new CreateTransactionInput(

                userId:                $purchasePayload->user->id,
                productId:             $purchasePayload->productId,
                paymentAmount:         $totalPaymentAmount,
                paymentMethod:         $purchasePayload->paymentMethod,
                paymentStatus:         self::INITIAL_PAYMENT_STATUS,
                idempotencyKey:        Str::uuid()->toString(),
                transactionId:         Str::uuid()->toString(),
                gatewayId:             null,
                last4DigitsCardNumber: $purchasePayload->last4DigitsCardNumber,
                cardBrand:             $purchasePayload->cardBrand,
                quantity:              $purchasePayload->quantity,
            ));

            $persistedTransactionId = $persistedTransaction->id;

            DB::afterCommit(function () use ($persistedTransactionId): void 
            {
                DispatchTransactionToFiscalJob::dispatch($persistedTransactionId);
            });

            return new TransactionCreated(
                idempotencyKey:         $persistedTransaction->idempotency_key,
                transactionId:          $persistedTransaction->transaction_id,
                paymentDate:            null,
                gatewayId:              $persistedTransaction->gateway_id,
                last4DigitsCardNumber:  $persistedTransaction->last_4_digits_card_number,
                paymentAmount:          $persistedTransaction->payment_amount,
                paymentMethod:          $persistedTransaction->payment_method,
                cardBrand:              $persistedTransaction->card_brand,
                status:                 $persistedTransaction->payment_status,
                quantity:               $persistedTransaction->quantity,
                user:                   $purchasePayload->user,
                productId:              $persistedTransaction->product_id,
            );
        });
    }
}
