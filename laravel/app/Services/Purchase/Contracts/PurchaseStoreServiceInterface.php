<?php

namespace App\Services\Purchase\Contracts;

use App\DTOs\Purchase\PurchaseStoreData;
use App\DTOs\Purchase\TransactionCreated;

/**
 * Service interface for creating a transaction and storing it in the database.
 */
interface PurchaseStoreServiceInterface
{
    /**
     * Create a transaction and store it in the database.
     */
    public function storePurchase(PurchaseStoreData $data): TransactionCreated;
}
