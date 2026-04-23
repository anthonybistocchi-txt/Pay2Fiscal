<?php

namespace App\Jobs;

use App\Integrations\Go\Contracts\DispatchTransactionToFiscalServiceInterface;
use App\Repositories\Purchase\Contract\TransactionRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Throwable;

class DispatchTransactionToFiscalJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const FAILED_PAYMENT_STATUS = 'ERROR';

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
        $dispatchTransactionToFiscal->dispatchByTransactionId($this->transactionId);
    }

    public function failed(?Throwable $exception): void
    {
        resolve(TransactionRepositoryInterface::class)->updatePaymentStatus(
            $this->transactionId,
            self::FAILED_PAYMENT_STATUS,
        );

        Log::error('Failed to dispatch transaction to fiscal service', [
            'transaction_id' => $this->transactionId,
            'error_hour'     => now()->format('Y-m-d H:i:s'),
            'exception'      => $exception,
            'error_message'  => $exception->getMessage(),
            'error_trace'    => $exception->getTraceAsString(),
            'error_file'     => $exception->getFile(),
            'error_line'     => $exception->getLine(),
            'error_code'     => $exception->getCode(),
        ]);
    }
}
