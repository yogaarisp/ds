<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'unit',
        'stock',
        'min_stock',
        'price_per_unit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function packagingRules()
    {
        return $this->hasMany(PackagingRule::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function isRawMaterial(): bool
    {
        return $this->type === 'raw_material';
    }

    public function isPackaging(): bool
    {
        return $this->type === 'packaging';
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRawMaterials($query)
    {
        return $query->where('type', 'raw_material');
    }

    public function scopePackaging($query)
    {
        return $query->where('type', 'packaging');
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }
}
