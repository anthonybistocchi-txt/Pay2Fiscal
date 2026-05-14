<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Integrations\Gateway\Contracts\DispatchPaymentGatewayServiceInterface;
use App\Models\Transaction;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
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

class DispatchPaymentGateway implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Sanitized failure message stored on the transaction when the job
     * exhausts its retries. Keeps internal details out of API responses.
     */
    private const PUBLIC_FAILURE_REASON = 'Payment dispatch failed after multiple attempts. Please retry later or contact support.';

    public int $tries = 5;

    /**
     * Wall-clock budget per attempt. Must be greater than the HTTP timeout
     * inside DispatchPaymentGateways so the worker is not killed mid-request.
     */
    public int $timeout = 45;

    /**
     * Window during which the unique lock is held. Prevents the lock from
     * becoming permanent if the worker dies before completing the job.
     */
    public int $uniqueFor = 600;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}

    public function uniqueId(): string
    {
        return 'payment-gateway-dispatch-'.$this->transaction->id;
    }

    /**
     * Exponential backoff (in seconds) between retries. Spreads load away
     * from a flapping gateway and gives transient failures time to clear.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 180, 300];
    }

    public function handle(
        DispatchPaymentGatewayServiceInterface $dispatchPaymentGatewayService,
        TransactionRepositoryInterface $transactionRepository,
    ): void 
    {
        Log::withContext([
            'payment_flow'       => true,
            'transaction_id'     => $this->transaction->id,
            'transaction_uuid'   => $this->transaction->transaction_uuid,
            'idempotency_key'    => $this->transaction->idempotency_key,
            'transaction_phase'  => 'payment_gateway_job',
        ]);

        Log::info('[Fluxo Pagamento] Job DispatchPaymentGateway iniciado', [
            'payment_status' => $this->transaction->payment_status->value,
            'job_attempt'    => $this->attempts(),
        ]);

        $isReadyToDispatch = in_array($this->transaction->payment_status, [
            PaymentStatus::PENDING,
            PaymentStatus::PROCESSING,
        ], true);

        if (!$isReadyToDispatch) 
        {
            Log::warning('[Fluxo Pagamento] Job abortado: transação não está PENDING nem PROCESSING', [
                'payment_status' => $this->transaction->payment_status->value,
            ]);

            throw new RuntimeException(sprintf(
                'Transaction %d is not ready to dispatch (current status: %s).',
                $this->transaction->id,
                $this->transaction->payment_status->value,
            ));
        }

        if ($this->transaction->payment_status === PaymentStatus::PENDING) 
        {
            Log::info('[Fluxo Pagamento] Marcando transação como PROCESSING antes do gateway');
            $transactionRepository->markAsProcessing($this->transaction);
        }

        Log::info('[Fluxo Pagamento] Chamando integração de gateways (HTTP)');
        $dispatchPaymentGatewayService->dispatch($this->transaction);

        Log::info('[Fluxo Pagamento] Job DispatchPaymentGateway concluiu handle sem exceção');
    }

    public function failed(?Throwable $exception): void
    {
        $transactionRepository = resolve(TransactionRepositoryInterface::class);

        try 
        {
            $transaction = $transactionRepository->findById($this->transaction->id);
        } 
        catch (ModelNotFoundException $modelNotFoundException) 
        {
            Log::error('Transaction not found when handling payment gateway job failure', [
                'transaction_id' => $this->transaction->id,
                'error_message'  => $modelNotFoundException->getMessage(),
                'job_attempts'   => $this->attempts(),
            ]);

            return;
        }

        if ($transaction->payment_status !== PaymentStatus::ERROR) 
        {
            $transactionRepository->markAsError($transaction, [
                'error_message' => self::PUBLIC_FAILURE_REASON,
                'error_code'    => $this->normalizeErrorCode($exception?->getCode()),
            ]);
        }

        Log::error('Failed to dispatch transaction to payment gateway', [
            'transaction_id'   => $transaction->id,
            'transaction_uuid' => $transaction->transaction_uuid,
            'job_attempts'     => $this->attempts(),
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
