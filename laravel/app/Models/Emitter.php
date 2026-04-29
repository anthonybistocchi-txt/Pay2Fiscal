<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Emitter extends Model
{
    protected $fillable = [
        'legal_name',
        'trade_name',
        'cnpj',
        'ie',
        'im',
        'tax_regime',
        'crt',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'country',
        'email',
        'phone',
    ];
}
