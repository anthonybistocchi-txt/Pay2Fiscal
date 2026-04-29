<?php

namespace App\Integrations\Fiscal\Contracts;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\RequestException;

/**
 * Sends a persisted transaction to the fiscal microservice for asynchronous processing.
 */
interface DispatchTransactionToFiscalServiceInterface
{
    /**
     * Loads the transaction by primary key and forwards it to the fiscal service.
     *
     * When {@see config('services.fiscal_api.base_url')} is empty, the call is skipped (local/dev).
     *
     * @throws RequestException When the HTTP response is not successful.
     * @throws ModelNotFoundException When the transaction does not exist.
     */
    public function dispatch(int $transactionPrimaryKey): void;
}

