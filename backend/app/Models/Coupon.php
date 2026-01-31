<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type', // percentage, fixed
        'value',
        'min_purchase',
        'max_discount',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_coupons')
                    ->withPivot('is_used', 'used_at')
                    ->withTimestamps();
    }
    
    /**
     * Check if coupon is valid for a purchase amount
     */
    public function isValidFor($amount)
    {
        if (!$this->is_active) return false;
        
        $now = now();
        if ($this->start_date && $this->start_date > $now) return false;
        if ($this->end_date && $this->end_date < $now) return false;
        
        if ($this->min_purchase > 0 && $amount < $this->min_purchase) return false;
        
        return true;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($amount)
    {
        if ($this->type === 'fixed') {
            return min($amount, $this->value);
        }

        // Percentage
        $discount = $amount * ($this->value / 100);
        if ($this->max_discount > 0) {
            $discount = min($discount, $this->max_discount);
        }

        return (int) $discount;
    }
}
