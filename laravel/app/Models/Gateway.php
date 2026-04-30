<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $fillable = [
        'name',
        'description',
        'base_url',
        'dispatch_path',
        'priority',
        'active',
    ];

    protected $casts = [
        'active'   => 'boolean',
        'priority' => 'integer',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
