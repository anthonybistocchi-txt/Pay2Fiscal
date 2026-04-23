<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $fillable = [
        'name',
        'description',
        'priority',
        'active',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
