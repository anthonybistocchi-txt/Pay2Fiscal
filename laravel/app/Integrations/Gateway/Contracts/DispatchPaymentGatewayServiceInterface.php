<?php

namespace App\Integrations\Gateway\Contracts;

use App\Models\Transaction;

interface DispatchPaymentGatewayServiceInterface
{
    /**
     * Loads the transaction by primary key and forwards it to the active payment gateways
     * (ordered by priority) until one accepts the dispatch.
     *
     * Preconditions: the transaction must be in PROCESSING status.
     * Outcomes: the transaction will be marked as APPROVED (success) or REJECTED (no gateway accepted).
     */
    public function dispatch(Transaction $transaction): void;
}
