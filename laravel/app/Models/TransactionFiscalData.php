<?php

namespace App\Models;

use App\Enums\FiscalStatus;
use Illuminate\Database\Eloquent\Model;

class TransactionFiscalData extends Model
{
    protected $table = 'transaction_fiscal_data';

    protected $fillable = [
        'transaction_id',
        'fiscal_status',
        'origin_product',
        'ncm',
        'cfop',
        'cest',
        'icms_cst_csosn',
        'pis_cst',
        'cofins_cst',
        'fiscal_request_id',
        'failure_reason',
        'error_code',
        'emitted_at',
    ];

    protected $casts = [
        'fiscal_status' => FiscalStatus::class,
        'error_code'    => 'integer',
        'emitted_at'    => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
