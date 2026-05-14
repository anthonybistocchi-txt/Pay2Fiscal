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
use Illuminate\Support\Facades\Log;
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
            Log::info('[Fluxo Pagamento] Início da transação de banco (compra)', [
                'transaction_phase' => 'db_transaction',
            ]);

            $product = Product::query()
                ->with('fiscal')
                ->lockForUpdate()
                ->findOrFail($purchasePayload->productId);

            $totalPaymentAmount = $product->price * $purchasePayload->quantity;

            Log::info('[Fluxo Pagamento] Produto bloqueado e valor total calculado', [
                'transaction_phase'   => 'product_locked',
                'unit_price'          => $product->price,
                'quantity'            => $purchasePayload->quantity,
                'total_payment_amount' => $totalPaymentAmount,
            ]);

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

            Log::info('[Fluxo Pagamento] Transação financeira obtida/criada por idempotência', [
                'transaction_phase'  => 'transaction_row',
                'transaction_id'     => $persistedTransaction->id,
                'transaction_uuid'   => $persistedTransaction->transaction_uuid,
                'created_new_row'    => $created,
                'current_status'     => $persistedTransaction->payment_status->value,
            ]);

            if ($created) 
            {
                $this->reserveStock($product->id, $purchasePayload->quantity);

                Log::info('[Fluxo Pagamento] Estoque reservado (se existir registro de estoque)', [
                    'transaction_phase' => 'stock_reserved',
                    'product_id'        => $product->id,
                ]);

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

                Log::info('[Fluxo Pagamento] Dados fiscais da transação gravados', [
                    'transaction_phase' => 'fiscal_data_created',
                    'transaction_id'    => $persistedTransaction->id,
                ]);

                DB::afterCommit(function () use ($persistedTransaction): void {
                    Log::info('[Fluxo Pagamento] Agendando job de envio ao gateway após commit', [
                        'transaction_phase' => 'queue_after_commit',
                        'transaction_id'    => $persistedTransaction->id,
                    ]);
                    DispatchPaymentGateway::dispatch($persistedTransaction);
                });
            } else {
                Log::info('[Fluxo Pagamento] Idempotência: reutilizando transação existente (sem novo job)', [
                    'transaction_phase' => 'idempotent_replay',
                    'transaction_id'    => $persistedTransaction->id,
                ]);
            }

            Log::info('[Fluxo Pagamento] Unidade de trabalho de banco concluída; montando resposta da compra', [
                'transaction_phase' => 'db_transaction_done',
                'transaction_id'    => $persistedTransaction->id,
                'payment_status'    => $persistedTransaction->payment_status->value,
            ]);

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
