<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageDiscount extends Model
{
    protected $fillable = [
        'package_id',
        'customer_type_id',
        'discount_amount'
    ];
    
    protected $with = ['customerType'];

    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }
    
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
