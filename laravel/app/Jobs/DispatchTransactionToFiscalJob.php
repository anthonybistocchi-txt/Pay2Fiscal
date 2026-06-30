<?php

namespace App\Jobs;

use App\Enums\FiscalStatus;
use App\Integrations\Fiscal\Contracts\DispatchTransactionToFiscalIntegrationInterface;
use App\Models\Transaction;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use App\Repositories\TransactionFiscalData\Contacts\TransactionFiscalDataRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DispatchTransactionToFiscalJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Sanitized failure message stored on the fiscal data when the job
     * exhausts its retries. Keeps internal details out of API responses.
     */
    private const PUBLIC_FAILURE_REASON = 'Fiscal dispatch failed after multiple attempts. Please retry later or contact support.';

    public int $tries = 5;

    /**
     * Wall-clock budget per attempt. Must exceed the HTTP timeout used by
     * the fiscal service so the worker is not killed mid-request.
     */
    public int $timeout = 45;

    /**
     * Window during which the unique lock is held. Prevents a permanent
     * lock when the worker dies before completing the job.
     */
    public int $uniqueFor = 600;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}

    public function uniqueId(): string
    {
        return 'fiscal-dispatch-'.$this->transaction->id;
    }

    /**
     * Exponential backoff (in seconds) between retries. The fiscal service
     * is third-party-dependent (city hall) and benefits from longer waits.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 90, 300, 900, 1800];
    }

    public function handle(DispatchTransactionToFiscalIntegrationInterface $dispatchTransactionToFiscalIntegration): void
    {
        Log::withContext([
            'payment_flow'       => true,
            'transaction_id'     => $this->transaction->id,
            'transaction_uuid'   => $this->transaction->transaction_uuid,
            'idempotency_key'    => $this->transaction->idempotency_key,
            'transaction_phase'  => 'fiscal_job',
        ]);

        Log::info('[Fluxo Pagamento] Job DispatchTransactionToFiscalJob iniciado', [
            'job_attempt' => $this->attempts(),
        ]);

        try {
            $dispatchTransactionToFiscalIntegration->dispatch($this->transaction);
        } catch (RuntimeException $exception) {
            Log::error('Fiscal dispatch failed', [
                'transaction_id'   => $this->transaction->id,
                'transaction_uuid' => $this->transaction->transaction_uuid,
                'job_attempts'     => $this->attempts(),
                'error_class'      => $exception ? $exception::class : null,
                'error_message'    => $exception?->getMessage(),
                'error_code'       => $exception?->getCode(),
                'error_file'       => $exception?->getFile(),
                'error_line'       => $exception?->getLine(),
            ]);
        }
        Log::info('[Fluxo Pagamento] Job DispatchTransactionToFiscalJob concluiu handle sem exceção');
    }

    public function failed(?Throwable $exception): void
    {
        $transactionRepository = resolve(TransactionRepositoryInterface::class);
        $fiscalDataRepository  = resolve(TransactionFiscalDataRepositoryInterface::class);

        try 
        {
            $transaction = $transactionRepository->findById($this->transaction->id);

        } 
        catch (ModelNotFoundException $modelNotFoundException) 
        {
            Log::error('Transaction not found when handling fiscal dispatch job failure', [
                'transaction_id' => $this->transaction->id,
                'error_message'  => $modelNotFoundException->getMessage(),
                'job_attempts'   => $this->attempts(),
            ]);

            return;
        }

        $fiscalData = $transaction->fiscalData;

        if ($fiscalData !== null && ! $fiscalData->fiscal_status->isFinal()) {
            $fiscalDataRepository->markAsError($fiscalData, [
                'error_message' => self::PUBLIC_FAILURE_REASON,
                'error_code'    => $this->normalizeErrorCode($exception?->getCode()),
            ]);
            
        }

        Log::error('Failed to dispatch transaction to fiscal service', [
            'transaction_id'   => $transaction->id,
            'transaction_uuid' => $transaction->transaction_uuid,
            'job_attempts'     => $this->attempts(),
            'fiscal_status'    => $fiscalData?->fiscal_status?->value,
            'error_class'      => $exception ? $exception::class : null,
            'error_message'    => $exception?->getMessage(),
            'error_code'       => $exception?->getCode(),
            'error_file'       => $exception?->getFile(),
            'error_line'       => $exception?->getLine(),
        ]);
    }

    /**
     * Persist only HTTP-like codes; otherwise drop them to avoid storing internal signals (e.g. 0).
     */
    private function normalizeErrorCode(mixed $code): ?int
    {
        return is_int($code) && $code >= 100 && $code <= 599 ? $code : null;
    }
}
