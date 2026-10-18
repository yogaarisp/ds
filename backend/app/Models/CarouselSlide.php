<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CarouselSlide extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'period_text',
        'image',
        'badge_label',
        'badge_value',
        'badge_label_2',
        'badge_value_2',
        'link_url',
        'start_date',
        'end_date',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['image_url'];

    protected static function booted()
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('carousel_slides_active');
        });

        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('carousel_slides_active');
        });
    }

    /**
     * Get full image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // If already a full URL, return as is
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        // Return relative URL for better compatibility
        return '/storage/' . $this->image;
    }

    /**
     * Scope for active slides within valid date range
     */
    public function scopeActive($query)
    {
        $today = now()->toDateString();
        
        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            });
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }
}
