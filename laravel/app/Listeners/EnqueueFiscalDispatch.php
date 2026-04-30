<?php

namespace App\Listeners;

use App\Enums\PaymentStatus;
use App\Events\TransactionApproved;
use App\Jobs\DispatchTransactionToFiscalJob;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;

class EnqueueFiscalDispatch
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function handle(TransactionApproved $event): void
    {
        $transaction = $this->transactionRepository->findById($event->transaction->id);

        if ($transaction->payment_status !== PaymentStatus::APPROVED) 
        {
            return;
        }

        DispatchTransactionToFiscalJob::dispatch($transaction);
    }
}
