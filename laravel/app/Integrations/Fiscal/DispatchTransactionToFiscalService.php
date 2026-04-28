<?php

namespace App\Integrations\Fiscal;

use App\Integrations\Fiscal\Contracts\DispatchTransactionToFiscalServiceInterface;
use App\Models\Transaction;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use RuntimeException;
use Throwable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DispatchTransactionToFiscalService implements DispatchTransactionToFiscalServiceInterface
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function dispatch(int $transactionPrimaryKey): void
    {
        $timeout      = (int)    config('services.fiscal_api.timeout');
        $baseUrl      = (string) config('services.fiscal_api.base_url');
        $dispatchPath = (string) config('services.fiscal_api.dispatch_path');

        if ($baseUrl === null || $baseUrl === '') 
        {
            Log::debug('Fiscal dispatch skipped: services.fiscal_api.base_url is not configured.', [
                'transaction_id' => $transactionPrimaryKey,
                'error_hour'     => now()->format('Y-m-d H:i:s'),
            ]);

            return;
        }

        $transaction = $this->transactionRepository->findById($transactionPrimaryKey);
        $dispatchUrl = rtrim($baseUrl, '/').'/'.ltrim($dispatchPath, '/');

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($dispatchUrl, $this->buildRequestPayload($transaction));
        } catch (Throwable $exception) {
            Log::warning('Failed to reach fiscal service', [
                'transaction_id'   => $transactionPrimaryKey,
                'transaction_uuid' => $transaction->transaction_uuid,
                'error_hour'       => now()->format('Y-m-d H:i:s'),
                'error_message'    => $exception->getMessage(),
                'error_class'      => $exception::class,
            ]);

            throw $exception;
        }

        if ($response->successful()) {
            $this->transactionRepository->markAsApproved($transactionPrimaryKey);
            return;
        }

        $errors = [
            'fiscal_response_code' => $response->status(),
            'fiscal_request_id'    => $response->json('request_id'),
            'failure_reason'       => $response->json('failure_reason'),
        ];

        $this->transactionRepository->markAsError($transactionPrimaryKey, $errors);

        Log::warning('Fiscal service rejected transaction dispatch', [
            'transaction_id'       => $transactionPrimaryKey,
            'transaction_uuid'     => $transaction->transaction_uuid,
            'error_hour'           => now()->format('Y-m-d H:i:s'),
            'failure_reason'       => $response->json('failure_reason'),
            'fiscal_response_code' => $response->status(),
            'fiscal_request_id'    => $response->json('request_id'),
        ]);

        throw new RuntimeException(
            'Failed to dispatch transaction to fiscal service: '.($errors['failure_reason'] ?? 'unknown reason'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestPayload(Transaction $transaction): array
    {
        return [
            'transaction_uuid'   => $transaction->transaction_uuid,
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

