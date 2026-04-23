<?php

namespace App\Integrations\Go;

use App\Integrations\Go\Contracts\DispatchTransactionToFiscalServiceInterface;
use App\Models\Transaction;
use App\Repositories\Purchase\Contract\TransactionRepositoryInterface;
use RuntimeException;
use Throwable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DispatchTransactionToFiscalService implements DispatchTransactionToFiscalServiceInterface
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function dispatchByTransactionId(int $transactionPrimaryKey): void
    {
        $goTimeout      = (int) config('services.fiscal_go.timeout');
        $goBaseUrl      = (string) config('services.fiscal_go.base_url');
        $goDispatchPath = (string) config('services.fiscal_go.dispatch_path');

        if ($goBaseUrl === null || $goBaseUrl === '') 
        {
            Log::debug('Fiscal Go dispatch skipped: services.fiscal_go.base_url is not configured.', [
                'transaction_id' => $transactionPrimaryKey,
                'error_hour'     => now()->format('Y-m-d H:i:s'),
            ]);
            
            return;
        }

        $transaction = $this->transactionRepository->findById($transactionPrimaryKey);

        $dispatchGoUrl = rtrim($goBaseUrl, '/').'/'.ltrim($goDispatchPath, '/');

        try {
            $goResponse = Http::timeout($goTimeout)
                ->acceptJson()
                ->asJson()
                ->post($dispatchGoUrl, $this->buildRequestPayload($transaction));
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

        if($goResponse->successful())
        {
            $this->transactionRepository->updatePaymentStatusSuccess($transactionPrimaryKey);
        }
        else
        {
            $goErrors = [
                'go_response_code' => $goResponse->status(),
                'go_request_id'    => $goResponse->json('request_id'),
                'failure_reason'   => $goResponse->json('failure_reason'),
            ];

            $this->transactionRepository->updatePaymentStatusFailed($transactionPrimaryKey, $goErrors);

            Log::warning('Fiscal service rejected transaction dispatch', [
                'transaction_id'   => $transactionPrimaryKey,
                'transaction_uuid' => $transaction->transaction_uuid,
                'error_hour'       => now()->format('Y-m-d H:i:s'),
                'failure_reason'   => $goResponse->json('failure_reason'),
                'go_response_code' => $goResponse->status(),
                'go_request_id'    => $goResponse->json('request_id'),
            ]);

            throw new RuntimeException(
                'Failed to dispatch transaction to fiscal service: '.($goErrors['failure_reason'] ?? 'unknown reason'),
            );
        }
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
