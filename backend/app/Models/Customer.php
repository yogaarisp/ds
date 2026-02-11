<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'avatar',
        'email',
        'address',
        'password',
        'type',
        'customer_type_id', // Replaces level
        'points',
        'discount_percentage',
        'total_orders',
        'total_spent',
        'last_order_date',
        'notes',
        'is_active',
    ];

    protected $with = ['customerType'];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_order_date' => 'date',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    
    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'customer_coupons')
                    ->withPivot('is_used', 'used_at')
                    ->withTimestamps();
    }
    
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class)->where('is_default', true);
    }

    public function isVip(): bool
    {
        return $this->type === 'vip';
    }

    public function isRegular(): bool
    {
        return $this->type === 'regular';
    }

    // Leagcy helpers mapped to new system
    public function isNormalLevel(): bool
    {
        return $this->customerType && $this->customerType->name === 'Biasa';
    }

    public function isResellerLevel(): bool
    {
        return $this->customerType && $this->customerType->name === 'Reseller';
    }

    public function isKeluargaLevel(): bool
    {
        return $this->customerType && $this->customerType->name === 'Keluarga';
    }

    public function getLevelLabel(): string
    {
        return $this->customerType ? $this->customerType->name : 'Biasa';
    }

    public function getDiscountAmount(int $basePrice): int
    {
        // Hanya gunakan custom discount percentage jika ada
        if ($this->discount_percentage > 0) {
            return (int) ($basePrice * $this->discount_percentage / 100);
        }
        
        // Tidak ada diskon default berdasarkan level
        // Diskon level harus diatur per produk/paket
        return 0;
    }

    public function getFinalPrice(int $basePrice): int
    {
        $discount = $this->getDiscountAmount($basePrice);
        return max(0, $basePrice - $discount);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVip($query)
    {
        return $query->where('type', 'vip');
    }

    public function scopeRegular($query)
    {
        return $query->where('type', 'regular');
    }

    public function scopeByLevel($query, $levelName)
    {
        return $query->whereHas('customerType', function($q) use ($levelName) {
            $q->where('name', $levelName);
        });
    }

    // Update customer stats after transaction
    public function updateStats()
    {
        $transactions = $this->transactions()->completed()->get();
        
        $this->update([
            'total_orders' => $transactions->count(),
            'total_spent' => $transactions->sum('total'),
            'last_order_date' => $transactions->max('created_at')?->toDateString(),
        ]);
    }

    // Format phone number for display
    public function getFormattedPhoneAttribute(): string
    {
        $phone = $this->phone;
        
        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // Format Indonesian phone number
        if (str_starts_with($phone, '62')) {
            return '+' . substr($phone, 0, 2) . ' ' . substr($phone, 2, 3) . '-' . substr($phone, 5, 4) . '-' . substr($phone, 9);
        } elseif (str_starts_with($phone, '08')) {
            return substr($phone, 0, 4) . '-' . substr($phone, 4, 4) . '-' . substr($phone, 8);
        }
        
        return $phone;
    }
}
