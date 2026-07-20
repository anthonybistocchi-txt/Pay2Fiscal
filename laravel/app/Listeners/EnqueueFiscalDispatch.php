<?php

namespace App\Listeners;

use App\Enums\FiscalStatus;
use App\Enums\PaymentStatus;
use App\Events\TransactionApproved;
use App\Jobs\DispatchTransactionToFiscalJob;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use App\Repositories\TransactionFiscalData\Contacts\TransactionFiscalDataRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnqueueFiscalDispatch implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Sanitized failure message stored on the fiscal data when the listener
     * exhausts its retries. Keeps internal details out of API responses.
     */
    private const PUBLIC_FAILURE_REASON = 'Fiscal dispatch could not be scheduled after multiple attempts. Please retry later or contact support.';

    public int $tries = 5;

    public int $timeout = 30;

    public int $uniqueFor = 600;

    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function uniqueId(TransactionApproved $event): string
    {
        return 'fiscal-enqueue-'.$event->transaction->id;
    }

    /**
     * Shorter backoff than the fiscal job: this step only reloads the
     * transaction and enqueues DispatchTransactionToFiscalJob.
     *
     * @return list<int>
     */
    public function backoff(TransactionApproved $event): array
    {
        return [10, 30, 60, 180, 300];
    }

    public function handle(TransactionApproved $event): void
    {
        Log::info('[Fluxo Pagamento] Evento TransactionApproved recebido (fila fiscal)', [
            'payment_flow'      => true,
            'transaction_phase' => 'fiscal_listener',
            'transaction_id'    => $event->transaction->id,
            'transaction_uuid'  => $event->transaction->transaction_uuid,
            'idempotency_key'   => $event->transaction->idempotency_key,
            'listener_attempt'  => $this->attempts(),
        ]);

        $transaction = $this->transactionRepository->findById($event->transaction->id);

        if ($transaction->payment_status !== PaymentStatus::APPROVED) {
            Log::warning('[Fluxo Pagamento] Listener fiscal ignorado: status não é APPROVED após reload', [
                'payment_status' => $transaction->payment_status->value,
            ]);

            return;
        }

        Log::info('[Fluxo Pagamento] Agendando DispatchTransactionToFiscalJob');

        DispatchTransactionToFiscalJob::dispatch($transaction);
    }

    public function failed(TransactionApproved $event, ?Throwable $exception): void
    {
        $fiscalDataRepository = resolve(TransactionFiscalDataRepositoryInterface::class);

        try {
            $transaction = $this->transactionRepository->findById($event->transaction->id);
        } catch (ModelNotFoundException $modelNotFoundException) {
            Log::error('Transaction not found when handling fiscal enqueue listener failure', [
                'transaction_id' => $event->transaction->id,
                'error_message' => $modelNotFoundException->getMessage(),
                'listener_attempts' => $this->attempts(),
            ]);

            return;
        }

        $fiscalData = $transaction->fiscalData;

        if ($fiscalData !== null && ! $fiscalData->fiscal_status->isFinal()) {
            $fiscalDataRepository->markAsError($fiscalData, [
                'error_message' => self::PUBLIC_FAILURE_REASON,
            ]);
        }

        Log::error('[Fluxo Pagamento] Failed to enqueue fiscal dispatch', [
            'transaction_id'   => $transaction->id,
            'transaction_uuid' => $transaction->transaction_uuid,
            'listener_attempts' => $this->attempts(),
            'fiscal_status'    => $fiscalData?->fiscal_status?->value,
            'error_class'      => $exception ? $exception::class : null,
            'error_message'    => $exception?->getMessage(),
            'error_file'       => $exception?->getFile(),
            'error_line'       => $exception?->getLine(),
        ]);
    }
}
