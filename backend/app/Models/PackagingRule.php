<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'rule_type',
        'trigger_quantity',
        'usage_quantity',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'usage_quantity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function isPerQuantity(): bool
    {
        return $this->rule_type === 'per_quantity';
    }

    public function isPerOrder(): bool
    {
        return $this->rule_type === 'per_order';
    }

    // Hitung pemakaian packaging berdasarkan total pcs
    public function calculateUsage(int $totalPcs): float
    {
        if ($this->isPerOrder()) {
            return $this->usage_quantity;
        }

        if ($this->isPerQuantity() && $this->trigger_quantity > 0) {
            return floor($totalPcs / $this->trigger_quantity) * $this->usage_quantity;
        }

        return 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
