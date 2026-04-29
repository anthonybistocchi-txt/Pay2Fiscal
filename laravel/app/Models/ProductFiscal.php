<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFiscal extends Model
{
    protected $table = 'product_fiscal';

    protected $fillable = [
        'product_id',
        'origin_id',
        'ncm',
        'cest',
        'cfop',
        'icms_cst_csosn',
        'pis_cst',
        'cofins_cst',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

