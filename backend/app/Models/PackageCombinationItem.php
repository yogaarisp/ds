<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageCombinationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_combination_id',
        'product_id',
        'quantity',
    ];

    public function combination()
    {
        return $this->belongsTo(PackageCombination::class, 'package_combination_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
