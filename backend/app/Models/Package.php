<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'total_quantity',
        'variant_count',
        'description',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function combinations()
    {
        return $this->hasMany(PackageCombination::class);
    }

    public function discounts()
    {
        return $this->hasMany(PackageDiscount::class);
    }

    public function isBundle(): bool
    {
        return $this->type === 'bundle';
    }

    public function isMix(): bool
    {
        return $this->type === 'mix';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBundles($query)
    {
        return $query->where('type', 'bundle');
    }

    public function scopeMixes($query)
    {
        return $query->where('type', 'mix');
    }
}
