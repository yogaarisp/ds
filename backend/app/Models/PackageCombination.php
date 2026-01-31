<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageCombination extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'name',
        'base_price',
        'is_active',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function items()
    {
        return $this->hasMany(PackageCombinationItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
