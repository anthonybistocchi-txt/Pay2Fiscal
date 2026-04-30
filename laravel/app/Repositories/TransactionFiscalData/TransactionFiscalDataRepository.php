<?php

namespace App\Repositories\TransactionFiscalData;

use App\Enums\FiscalStatus;
use App\Models\TransactionFiscalData;
use App\Repositories\TransactionFiscalData\Contacts\TransactionFiscalDataRepositoryInterface;
use App\Repositories\TransactionFiscalData\DTO\CreateTransactionFiscalDataInput;

final class TransactionFiscalDataRepository implements TransactionFiscalDataRepositoryInterface
{
    public function create(CreateTransactionFiscalDataInput $input): TransactionFiscalData
    {
        return TransactionFiscalData::create([
            'transaction_id'    => $input->transactionId,
            'origin_product'    => $input->originProduct,
            'ncm'               => $input->ncm,
            'cfop'              => $input->cfop,
            'cest'              => $input->cest,
            'icms_cst_csosn'    => $input->icmsCstCsosn,
            'pis_cst'           => $input->pisCst,
            'cofins_cst'        => $input->cofinsCst,
            'fiscal_request_id' => $input->fiscalRequestId,
            'failure_reason'    => $input->failureReason,
        ]);
    }

    public function markAsProcessing(TransactionFiscalData $fiscalData): void
    {
        $fiscalData->update([
            'fiscal_status' => FiscalStatus::PROCESSING,
        ]);
    }

    public function markAsEmitted(TransactionFiscalData $fiscalData, ?string $fiscalRequestId = null): void
    {
        $fiscalData->update([
            'fiscal_status'     => FiscalStatus::EMITTED,
            'fiscal_request_id' => $fiscalRequestId,
            'failure_reason'    => null,
            'error_code'        => null,
            'emitted_at'        => now(),
        ]);
    }

    public function markAsRejected(TransactionFiscalData $fiscalData, array $errors): void
    {
        $fiscalData->update([
            'fiscal_status'     => FiscalStatus::REJECTED,
            'fiscal_request_id' => $errors['fiscal_request_id'] ?? $fiscalData->fiscal_request_id,
            'failure_reason'    => $errors['error_message'] ?? null,
            'error_code'        => $errors['error_code'] ?? null,
            'emitted_at'        => null,
        ]);
    }

    public function markAsError(TransactionFiscalData $fiscalData, array $errors): void
    {
        $fiscalData->update([
            'fiscal_status'     => FiscalStatus::ERROR,
            'fiscal_request_id' => $errors['fiscal_request_id'] ?? $fiscalData->fiscal_request_id,
            'failure_reason'    => $errors['error_message'] ?? null,
            'error_code'        => $errors['error_code'] ?? null,
            'emitted_at'        => null,
        ]);
    }
}
