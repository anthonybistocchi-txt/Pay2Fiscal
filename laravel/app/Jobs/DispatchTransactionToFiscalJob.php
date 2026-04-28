<?php

namespace App\Jobs;

use App\Integrations\Fiscal\Contracts\DispatchTransactionToFiscalServiceInterface;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchTransactionToFiscalJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const ERROR_PAYMENT_STATUS = 'ERROR';

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $transactionId,
    ) {}

    public function uniqueId(): string
    {
        return 'fiscal-dispatch-'.$this->transactionId;
    }

    public function handle(DispatchTransactionToFiscalServiceInterface $dispatchTransactionToFiscal): void
    {
        $dispatchTransactionToFiscal->dispatch($this->transactionId);
    }

    public function failed(?Throwable $exception): void
    {
        $transactionRepository = resolve(TransactionRepositoryInterface::class);

        try 
        {
            $transaction = $transactionRepository->findById($this->transactionId);
        } 
        catch (ModelNotFoundException $modelNotFoundException) 
        {
            Log::error('Failed to update transaction after dispatch job failure: transaction not found', [
                'transaction_id' => $this->transactionId,
                'error_hour'     => now()->format('Y-m-d H:i:s'),
                'error_message'  => $modelNotFoundException->getMessage(),
                'job_attempts'   => $this->attempts(),
            ]);

            return;
        }

        $alreadyStoredDetailedError = $transaction->payment_status === self::ERROR_PAYMENT_STATUS
            && (
                $transaction->failure_reason      !== null
                || $transaction->fiscal_response_code !== null
                || $transaction->fiscal_request_id    !== null
            );

        if (!$alreadyStoredDetailedError) 
        {
            $exceptionCode = $exception?->getCode();
            
            $safeHttpLikeCode = is_int($exceptionCode) && $exceptionCode >= 100 && $exceptionCode <= 599
                ? $exceptionCode
                : null;

            $transactionRepository->markAsError(
                $this->transactionId,
                [
                    'failure_reason'   => $exception?->getMessage() ?? 'Job failed without exception detail',
                    'fiscal_response_code' => $safeHttpLikeCode,
                    'fiscal_request_id'    => null,
                ],
            );
        }

        Log::error('Failed to dispatch transaction to fiscal service', [
            'transaction_id'                => $this->transactionId,
            'transaction_uuid'              => $transaction->transaction_uuid,
            'error_hour'                    => now()->format('Y-m-d H:i:s'),
            'job_attempts'                  => $this->attempts(),
            'already_stored_detailed_error' => $alreadyStoredDetailedError,
            'exception'                     => $exception,
            'error_message'                 => $exception?->getMessage(),
            'error_trace'                   => $exception?->getTraceAsString(),
            'error_file'                    => $exception?->getFile(),
            'error_line'                    => $exception?->getLine(),
            'error_code'                    => $exception?->getCode(),
        ]);
    }
}
