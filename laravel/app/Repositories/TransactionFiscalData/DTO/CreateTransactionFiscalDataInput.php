<?php

namespace App\Repositories\TransactionFiscalData\DTO;

final class CreateTransactionFiscalDataInput
{
    public function __construct(
        public readonly int     $transactionId,
        public readonly ?int    $originProduct,
        public readonly ?string $ncm,
        public readonly ?string $cfop,
        public readonly ?string $cest,
        public readonly ?string $icmsCstCsosn,
        public readonly ?string $pisCst,
        public readonly ?string $cofinsCst,
        public readonly ?string $fiscalRequestId,
        public readonly ?string $failureReason,
    ) {}
}
