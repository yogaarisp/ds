<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'name',
        'code',
        'status',
        'capacity',
    ];

    protected $appends = ['qr_url'];

    /**
     * Get transactions for this table
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get QR code URL for this table
     */
    public function getQrUrlAttribute()
    {
        // Read directly from env to avoid config cache issues
        $baseUrl = env('FRONTEND_URL', 'http://localhost:5173');
        return $baseUrl . '/customer/home?table=' . $this->code;
    }

    /**
     * Check if table has active (unpaid) orders
     */
    public function hasActiveOrders()
    {
        return $this->transactions()
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();
    }
}
