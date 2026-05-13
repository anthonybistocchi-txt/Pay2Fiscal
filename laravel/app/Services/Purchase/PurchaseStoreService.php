<?php

declare(strict_types=1);

namespace App\Services\Purchase;

use App\DTOs\Purchase\PurchaseStoreData;
use App\DTOs\Purchase\TransactionCreated;
use App\Enums\PaymentStatus;
use App\Exceptions\Purchase\InsufficientStockException;
use App\Jobs\DispatchPaymentGateway;
use App\Models\Product;
use App\Models\Stock;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use App\Repositories\Transaction\DTO\CreateTransactionInput;
use App\Repositories\TransactionFiscalData\Contacts\TransactionFiscalDataRepositoryInterface;
use App\Repositories\TransactionFiscalData\DTO\CreateTransactionFiscalDataInput;
use App\Services\Purchase\Contracts\PurchaseStoreServiceInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PurchaseStoreService implements PurchaseStoreServiceInterface
{
    /**
     * Number of times the SQL transaction is retried in case of deadlock.
     */
    private const TRANSACTION_ATTEMPTS = 3;

    public function __construct(
        private readonly ConnectionInterface $database,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionFiscalDataRepositoryInterface $transactionFiscalDataRepository,
    ) {}

    public function storePurchase(PurchaseStoreData $purchasePayload): TransactionCreated
    {
        return $this->database->transaction(function () use ($purchasePayload): TransactionCreated {
            $product = Product::query()
                ->with('fiscal')
                ->lockForUpdate()
                ->findOrFail($purchasePayload->productId);

            $totalPaymentAmount = $product->price * $purchasePayload->quantity;

            [$persistedTransaction, $created] = $this->transactionRepository->firstOrCreateByIdempotencyKey(new CreateTransactionInput(
                userId:                $purchasePayload->user->id,
                productId:             $product->id,
                paymentAmount:         $totalPaymentAmount,
                paymentMethod:         $purchasePayload->paymentMethod,
                paymentStatus:         PaymentStatus::PENDING->value,
                idempotencyKey:        $purchasePayload->idempotencyKey,
                transactionUuid:       Str::uuid()->toString(),
                gatewayId:             null,
                last4DigitsCardNumber: $purchasePayload->last4DigitsCardNumber,
                cardBrand:             $purchasePayload->cardBrand,
                quantity:              $purchasePayload->quantity,
            ));

            if ($created) 
            {
                $this->reserveStock($product->id, $purchasePayload->quantity);

                $this->transactionFiscalDataRepository->create(new CreateTransactionFiscalDataInput(
                    transactionId:   $persistedTransaction->id,
                    originProduct:   $product->fiscal?->origin_product,
                    ncm:             $product->fiscal?->ncm,
                    cfop:            $product->fiscal?->cfop,
                    cest:            $product->fiscal?->cest,
                    icmsCstCsosn:    $product->fiscal?->icms_cst_csosn,
                    pisCst:          $product->fiscal?->pis_cst,
                    cofinsCst:       $product->fiscal?->cofins_cst,
                    fiscalRequestId: null,
                    failureReason:   null,
                ));

                DB::afterCommit(function () use ($persistedTransaction): void {
                    DispatchPaymentGateway::dispatch($persistedTransaction);
                });
            }

            return new TransactionCreated(
                idempotencyKey:         $persistedTransaction->idempotency_key,
                paymentDate:            $persistedTransaction->payment_date,
                gatewayId:              $persistedTransaction->gateway_id,
                last4DigitsCardNumber:  $persistedTransaction->last_4_digits_card_number,
                paymentAmount:          $persistedTransaction->payment_amount,
                paymentMethod:          $persistedTransaction->payment_method,
                cardBrand:              $persistedTransaction->card_brand,
                status:                 $persistedTransaction->payment_status->value,
                quantity:               $persistedTransaction->quantity,
                user:                   $purchasePayload->user,
                productId:              $persistedTransaction->product_id,
                transactionUuid:        $persistedTransaction->transaction_uuid,
            );
        }, self::TRANSACTION_ATTEMPTS);
    }

    /**
     * Locks the stock row for the product and atomically decrements the
     * available quantity. When no stock row exists, the call is a no-op
     * (stock control is opt-in per product).
     *
     * @throws InsufficientStockException
     */
    private function reserveStock(int $productId, int $quantity): void
    {
        $stock = Stock::query()
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock === null) 
        {
            return;
        }

        if ($stock->quantity < $quantity) 
        {
            throw new InsufficientStockException(
                productId: $productId,
                requested: $quantity,
                available: $stock->quantity,
            );
        }

        $stock->decrement('quantity', $quantity);
    }
}
