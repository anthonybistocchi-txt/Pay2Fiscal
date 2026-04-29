<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionFiscalData extends Model
{
    protected $table = 'transaction_fiscal_data';

    protected $fillable = [
        'transaction_id',
        'origin_id',
        'ncm',
        'cfop',
        'cest',
        'icms_cst_csosn',
        'pis_cst',
        'cofins_cst',
        'fiscal_response_code',
        'fiscal_request_id',
        'failure_reason',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}

