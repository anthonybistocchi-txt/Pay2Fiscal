<?php

namespace App\Repositories\TransactionFiscalData\Contacts;

use App\Models\TransactionFiscalData;
use App\Repositories\TransactionFiscalData\DTO\CreateTransactionFiscalDataInput;

interface TransactionFiscalDataRepositoryInterface
{
    public function create(CreateTransactionFiscalDataInput $input): TransactionFiscalData;

    public function markAsProcessing(TransactionFiscalData $fiscalData): void;

    public function markAsEmitted(TransactionFiscalData $fiscalData, ?string $fiscalRequestId = null): void;

    public function markAsRejected(TransactionFiscalData $fiscalData, array $errors): void;

    public function markAsDenied(TransactionFiscalData $fiscalData, array $errors): void;

    public function markAsError(TransactionFiscalData $fiscalData, array $errors): void;

    public function cancelDueToPaymentFailure(?TransactionFiscalData $fiscalData, string $reason): void;
}
