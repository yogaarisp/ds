<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'type',
        'account_name',
        'account_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
