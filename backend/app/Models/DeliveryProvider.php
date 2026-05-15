<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'logo_url',
        'type',
        'api_config',
        'is_active',
        'priority',
        'base_cost',
        'cost_per_km',
        'max_distance',
        'base_time_minutes',
        'time_per_km_minutes',
    ];

    protected $casts = [
        'api_config' => 'array',
        'is_active' => 'boolean',
        'base_cost' => 'decimal:2',
        'cost_per_km' => 'decimal:2',
        'max_distance' => 'decimal:2',
    ];

    /**
     * Get active providers ordered by priority
     */
    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    /**
     * Calculate delivery cost based on distance
     */
    public function calculateCost($distanceKm)
    {
        $cost = $this->base_cost + ($distanceKm * $this->cost_per_km);
        
        // Round to nearest 500
        return ceil($cost / 500) * 500;
    }

    /**
     * Calculate estimated delivery time
     */
    public function calculateTime($distanceKm)
    {
        return $this->base_time_minutes + ($distanceKm * $this->time_per_km_minutes);
    }

    /**
     * Check if distance is within delivery range
     */
    public function canDeliver($distanceKm)
    {
        return $distanceKm <= $this->max_distance;
    }
}