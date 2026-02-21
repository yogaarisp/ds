<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\TransactionObserver;

#[ObservedBy([TransactionObserver::class])]
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'customer_id',
        'customer_name',
        'table_id',
        'order_type',
        'scheduled_date',
        'scheduled_time',
        'subtotal',
        'discount',
        'total',
        'paid_amount',
        'change_amount',
        'dp_amount',
        'remaining_amount',
        'payment_status',
        'dp_paid_at',
        'fully_paid_at',
        'status',
        'notes',
        'payment_method_id',
        'queue_number',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'dp_paid_at' => 'datetime',
            'fully_paid_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function isInstant(): bool
    {
        return $this->order_type === 'instant';
    }

    public function isScheduled(): bool
    {
        return $this->order_type === 'scheduled';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // DP Payment Status Helpers
    public function isDp(): bool
    {
        return $this->payment_status === 'dp';
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === 'unpaid';
    }

    public function hasRemainingBalance(): bool
    {
        return $this->remaining_amount > 0;
    }

    public function getRemainingToPayAttribute(): int
    {
        return max(0, $this->total - $this->paid_amount - $this->dp_amount);
    }

    // Generate invoice number
    public static function generateInvoiceNumber(): string
    {
        $now = now();
        // Format: DSddMMyyHHmm (e.g. DS0401261830)
        // Matches frontend preview format
        $prefix = 'DS' . $now->format('dmyHi');
        
        // Check if invoice number already exists
        if (self::where('invoice_number', $prefix)->exists()) {
            // If exists, append seconds
            $prefix .= $now->format('s');
            
            // If still exists (very fast transactions), append random number
            if (self::where('invoice_number', $prefix)->exists()) {
                return $prefix . rand(10, 99);
            }
        }

        return $prefix;
    }

    /**
     * Generate daily resetting queue number
     */
    public static function generateQueueNumber(): int
    {
        $lastQueueNumber = self::whereDate('created_at', today())
            ->whereNotNull('queue_number')
            ->max('queue_number');

        return ($lastQueueNumber ?: 0) + 1;
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeScheduled($query)
    {
        return $query->where('order_type', 'scheduled');
    }

    public function scopeUpcoming($query)
    {
        return $query->scheduled()
            ->where(function($q) {
                // Show pending orders (regardless of date)
                $q->where('status', 'pending')
                  // OR show completed orders that are still upcoming (not past due)
                  ->orWhere(function($subQ) {
                      $subQ->where('status', 'completed')
                           ->where('scheduled_date', '>=', now()->toDateString());
                  });
            });
    }
}
