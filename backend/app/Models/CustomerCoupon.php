<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CustomerCoupon extends Pivot
{
    protected $table = 'customer_coupons';

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];
}
