<?php

namespace App\Integrations\Go;

use App\Integrations\Go\Contracts\DispatchTransactionToFiscalServiceInterface;
use App\Models\Transaction;
use App\Repositories\Purchase\Contract\TransactionRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DispatchTransactionToFiscalService implements DispatchTransactionToFiscalServiceInterface
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    CONST GO_BASE_URL = config('services.fiscal_go.base_url');
    CONST GO_TIMEOUT = config('services.fiscal_go.timeout');
    CONST GO_DISPATCH_PATH = config('services.fiscal_go.dispatch_path');
    CONST FAILED_PAYMENT_STATUS = 'FAILED_PAYMENT';
    CONST PAID_PAYMENT_STATUS = 'PAID_PAYMENT';
    CONST PENDING_PAYMENT_STATUS = 'PENDING_PAYMENT';

    public function dispatchByTransactionId(int $transactionPrimaryKey): void
    {
        if (self::GO_BASE_URL === null || self::GO_BASE_URL === '') 
        {
            Log::debug('Fiscal Go dispatch skipped: services.fiscal_go.base_url is not configured.', [
                'transaction_id' => $transactionPrimaryKey,
                'error_hour'     => now()->format('Y-m-d H:i:s'),
            ]);

            return;
        }

        $transaction = $this->transactionRepository->findById($transactionPrimaryKey);

        if(!$transaction)
        {
            Log::error('Transaction not found', [
                'transaction_id' => $transactionPrimaryKey,
                'error_hour'     => now()->format('Y-m-d H:i:s'),
                'error_message'  => 'Transaction not found',
                'error_file'     => __FILE__,
                'error_line'     => __LINE__,
            ]);

            $this->transactionRepository->updatePaymentStatus($transactionPrimaryKey, self::FAILED_PAYMENT_STATUS);

            return;
        }

        $requestTimeoutSeconds = (int) self::GO_TIMEOUT;
        $dispatchUrl           = rtrim((string) self::GO_BASE_URL, '/').'/'.ltrim((string) self::GO_DISPATCH_PATH, '/');

        Http::timeout($requestTimeoutSeconds)
            ->acceptJson()
            ->asJson()
            ->post($dispatchUrl, $this->buildRequestPayload($transaction))
            ->throw()
            ->onSuccess(function () use ($transactionPrimaryKey): void 
            {
                $this->transactionRepository->updatePaymentStatus($transactionPrimaryKey, self::PAID_PAYMENT_STATUS);
            })
            ->onError(function () use ($transactionPrimaryKey): void 
            {
                $this->transactionRepository->updatePaymentStatus($transactionPrimaryKey, self::FAILED_PAYMENT_STATUS);
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestPayload(Transaction $transaction): array
    {
        return [
            'transaction_uuid'   => $transaction->transaction_id,
            'idempotency_key'    => $transaction->idempotency_key,
            'user_id'            => $transaction->user_id,
            'product_id'         => $transaction->product_id,
            'quantity'           => $transaction->quantity,
            'payment_amount'     => $transaction->payment_amount,
            'payment_method'     => $transaction->payment_method,
            'payment_status'     => $transaction->payment_status,
            'card_brand'         => $transaction->card_brand,
            'last_4_digits_card' => $transaction->last_4_digits_card_number,
        ];
    }
}
