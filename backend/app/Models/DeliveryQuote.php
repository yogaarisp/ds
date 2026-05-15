<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_code',
        'origin_lat',
        'origin_lng',
        'dest_lat',
        'dest_lng',
        'cost',
        'estimated_minutes',
        'service_type',
        'provider_response',
        'expires_at',
    ];

    protected $casts = [
        'origin_lat' => 'decimal:8',
        'origin_lng' => 'decimal:8',
        'dest_lat' => 'decimal:8',
        'dest_lng' => 'decimal:8',
        'cost' => 'decimal:2',
        'provider_response' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Find cached quote
     */
    public static function findCached($providerCode, $originLat, $originLng, $destLat, $destLng)
    {
        return static::where('provider_code', $providerCode)
            ->where('origin_lat', $originLat)
            ->where('origin_lng', $originLng)
            ->where('dest_lat', $destLat)
            ->where('dest_lng', $destLng)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Cache a new quote
     */
    public static function cache($providerCode, $originLat, $originLng, $destLat, $destLng, $cost, $estimatedMinutes, $serviceType = null, $providerResponse = null)
    {
        return static::create([
            'provider_code' => $providerCode,
            'origin_lat' => $originLat,
            'origin_lng' => $originLng,
            'dest_lat' => $destLat,
            'dest_lng' => $destLng,
            'cost' => $cost,
            'estimated_minutes' => $estimatedMinutes,
            'service_type' => $serviceType,
            'provider_response' => $providerResponse,
            'expires_at' => now()->addMinutes(15), // Cache for 15 minutes
        ]);
    }

    /**
     * Clean expired quotes
     */
    public static function cleanExpired()
    {
        return static::where('expires_at', '<', now())->delete();
    }
}