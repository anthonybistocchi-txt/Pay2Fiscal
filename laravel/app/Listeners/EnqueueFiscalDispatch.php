<?php

namespace App\Listeners;

use App\Enums\PaymentStatus;
use App\Events\TransactionApproved;
use App\Jobs\DispatchTransactionToFiscalJob;
use App\Repositories\Transaction\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\Log;

class EnqueueFiscalDispatch
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function handle(TransactionApproved $event): void
    {
        Log::info('[Fluxo Pagamento] Evento TransactionApproved recebido (fila fiscal)', [
            'payment_flow'      => true,
            'transaction_phase' => 'fiscal_listener',
            'transaction_id'     => $event->transaction->id,
            'transaction_uuid'   => $event->transaction->transaction_uuid,
            'idempotency_key'    => $event->transaction->idempotency_key,
        ]);

        $transaction = $this->transactionRepository->findById($event->transaction->id);

        if ($transaction->payment_status !== PaymentStatus::APPROVED) 
        {
            Log::warning('[Fluxo Pagamento] Listener fiscal ignorado: status não é APPROVED após reload', [
                'payment_status' => $transaction->payment_status->value,
            ]);

            return;
        }

        Log::info('[Fluxo Pagamento] Agendando DispatchTransactionToFiscalJob');

        DispatchTransactionToFiscalJob::dispatch($transaction);
    }
}
