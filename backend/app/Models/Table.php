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

    // Status constants
    const STATUS_AVAILABLE = 'available';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_FINISHING = 'finishing';
    const STATUS_RESERVED = 'reserved';

    /**
     * Get transactions for this table
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get active (unpaid) transactions for this table
     */
    public function activeTransactions()
    {
        return $this->hasMany(Transaction::class)
            ->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Get the latest completed transaction (for finishing state info)
     */
    public function latestTransaction()
    {
        return $this->hasOne(Transaction::class)
            ->latest('updated_at');
    }

    /**
     * Get QR code URL for this table
     */
    public function getQrUrlAttribute()
    {
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

    /**
     * Update table status based on its active transactions.
     * Called by TransactionObserver after transaction changes.
     */
    public function refreshStatus(): void
    {
        $hasPending = $this->transactions()
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($hasPending) {
            $this->update(['status' => self::STATUS_OCCUPIED]);
            return;
        }

        // If no pending orders, check if there was a recently completed one
        // (within today) — if so, set to finishing
        $hasRecentCompleted = $this->transactions()
            ->where('status', 'completed')
            ->whereDate('updated_at', today())
            ->exists();

        if ($hasRecentCompleted && $this->status === self::STATUS_OCCUPIED) {
            $this->update(['status' => self::STATUS_FINISHING]);
            return;
        }

        // If status was already finishing, keep it (manual clear required)
        // If status is reserved, keep it
        if (in_array($this->status, [self::STATUS_FINISHING, self::STATUS_RESERVED])) {
            return;
        }

        // Default: available
        $this->update(['status' => self::STATUS_AVAILABLE]);
    }

    /**
     * Clear table (manual action by staff)
     */
    public function clearTable(): void
    {
        $this->update(['status' => self::STATUS_AVAILABLE]);
    }
}
