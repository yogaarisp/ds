<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'store_latitude',
        'store_longitude',
        'store_address',
        'minimum_order',
        'is_delivery_active',
        'operating_hours',
    ];

    protected $casts = [
        'store_latitude' => 'decimal:8',
        'store_longitude' => 'decimal:8',
        'minimum_order' => 'decimal:2',
        'is_delivery_active' => 'boolean',
        'operating_hours' => 'array',
    ];

    /**
     * Get the singleton delivery settings
     */
    public static function getSettings()
    {
        return static::first() ?? static::create([
            'store_name' => 'Dimsum Sarang',
            'store_latitude' => -6.2088,
            'store_longitude' => 106.8456,
            'store_address' => 'Jakarta',
            'minimum_order' => 25000,
            'is_delivery_active' => true,
        ]);
    }

    /**
     * Check if delivery is currently available
     */
    public function isDeliveryAvailable()
    {
        if (!$this->is_delivery_active) {
            return false;
        }

        $currentDay = strtolower(now()->format('l')); // monday, tuesday, etc
        $currentTime = now()->format('H:i');

        $hours = $this->operating_hours[$currentDay] ?? null;
        
        if (!$hours || count($hours) < 2) {
            return true; // No restrictions set
        }

        return $currentTime >= $hours[0] && $currentTime <= $hours[1];
    }

    /**
     * Get store coordinates as array
     */
    public function getCoordinates()
    {
        return [
            'lat' => (float) $this->store_latitude,
            'lng' => (float) $this->store_longitude
        ];
    }
}