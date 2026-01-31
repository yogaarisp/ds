<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'item_type',
        'product_id',
        'package_id',
        'package_combination_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function packageCombination()
    {
        return $this->belongsTo(PackageCombination::class);
    }

    public function isProduct(): bool
    {
        return $this->item_type === 'product';
    }

    public function isPackage(): bool
    {
        return $this->item_type === 'package';
    }

    // Hitung total pcs untuk item ini
    public function getTotalPcs(): int
    {
        if ($this->isProduct()) {
            return $this->quantity;
        }

        if ($this->isPackage() && $this->package) {
            return $this->package->total_quantity * $this->quantity;
        }

        return 0;
    }
}
