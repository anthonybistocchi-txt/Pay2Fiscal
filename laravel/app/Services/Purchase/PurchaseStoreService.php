<?php

namespace App\Services\Purchase;

use App\DTOs\Purchase\PurchaseStoreData;
use App\DTOs\Purchase\TransactionCreated;
use App\Enums\PaymentStatus;
use App\Jobs\DispatchPaymentGateway;
use App\Models\Product;
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
    public function __construct(
        private readonly ConnectionInterface $database,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionFiscalDataRepositoryInterface $transactionFiscalDataRepository,
    ) {}

    public function storePurchase(PurchaseStoreData $purchasePayload): TransactionCreated
    {
        return $this->database->transaction(function () use ($purchasePayload): TransactionCreated 
        {
            $product = Product::query()
                ->with('fiscal')
                ->findOrFail($purchasePayload->productId);

            $totalPaymentAmount = $product->price * $purchasePayload->quantity;

            $persistedTransaction = $this->transactionRepository->create(new CreateTransactionInput(

                userId:                $purchasePayload->user->id,
                productId:             $product->id,
                paymentAmount:         $totalPaymentAmount,
                paymentMethod:         $purchasePayload->paymentMethod,
                paymentStatus:         PaymentStatus::PENDING->value,
                idempotencyKey:        Str::uuid()->toString(),
                transactionUuid:       Str::uuid()->toString(),
                gatewayId:             null,
                last4DigitsCardNumber: $purchasePayload->last4DigitsCardNumber,
                cardBrand:             $purchasePayload->cardBrand,
                quantity:              $purchasePayload->quantity,
            ));

            $persistedTransactionId = $persistedTransaction->id;

            $this->transactionFiscalDataRepository->create(new CreateTransactionFiscalDataInput(
                transactionId:   $persistedTransactionId,
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

            DB::afterCommit(function () use ($persistedTransaction): void 
            {
                DispatchPaymentGateway::dispatch($persistedTransaction); // Envia o job para o dispatcher de pagamentos
            });

            return new TransactionCreated(
                idempotencyKey:         $persistedTransaction->idempotency_key,
                paymentDate:            null,
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
        });
    }
}
