<?php

namespace App\Services;

use App\Models\DeliveryProvider;
use App\Models\DeliverySetting;
use App\Models\DeliveryQuote;
use App\Models\CustomerAddress;
use Exception;

class DeliveryService
{
    /**
     * Calculate delivery options for a customer address
     */
    public function calculateDeliveryOptions($addressId)
    {
        $address = CustomerAddress::findOrFail($addressId);
        $settings = DeliverySetting::getSettings();
        
        if (!$settings->isDeliveryAvailable()) {
            throw new Exception('Delivery tidak tersedia saat ini');
        }

        $storeCoords = $settings->getCoordinates();
        $customerCoords = [
            'lat' => (float) $address->latitude,
            'lng' => (float) $address->longitude
        ];

        // Calculate distance
        $distance = $this->calculateDistance(
            $storeCoords['lat'],
            $storeCoords['lng'],
            $customerCoords['lat'],
            $customerCoords['lng']
        );

        $providers = DeliveryProvider::getActive();
        $options = [];

        foreach ($providers as $provider) {
            if (!$provider->canDeliver($distance)) {
                continue;
            }

            // Check cache first
            $cached = DeliveryQuote::findCached(
                $provider->code,
                $storeCoords['lat'],
                $storeCoords['lng'],
                $customerCoords['lat'],
                $customerCoords['lng']
            );

            if ($cached) {
                $options[] = [
                    'provider' => $provider->code,
                    'name' => $provider->name,
                    'type' => $provider->type,
                    'cost' => $cached->cost,
                    'estimated_minutes' => $cached->estimated_minutes,
                    'distance_km' => round($distance, 2),
                    'can_deliver' => true,
                    'cached' => true
                ];
                continue;
            }

            // Calculate new quote
            $cost = $provider->calculateCost($distance);
            $time = $provider->calculateTime($distance);

            // Apply conditions (weather, peak hours, etc.)
            $adjusted = $this->applyConditions($cost, $time);

            $option = [
                'provider' => $provider->code,
                'name' => $provider->name,
                'type' => $provider->type,
                'cost' => $adjusted['cost'],
                'estimated_minutes' => $adjusted['time'],
                'distance_km' => round($distance, 2),
                'can_deliver' => true,
                'cached' => false
            ];

            // Cache the quote
            DeliveryQuote::cache(
                $provider->code,
                $storeCoords['lat'],
                $storeCoords['lng'],
                $customerCoords['lat'],
                $customerCoords['lng'],
                $adjusted['cost'],
                $adjusted['time'],
                $provider->type
            );

            $options[] = $option;
        }

        if (empty($options)) {
            throw new Exception('Tidak ada kurir yang tersedia untuk alamat ini');
        }

        // Sort by cost (cheapest first)
        usort($options, function($a, $b) {
            return $a['cost'] <=> $b['cost'];
        });

        return [
            'address' => [
                'id' => $address->id,
                'label' => $address->label,
                'recipient_name' => $address->recipient_name,
                'address' => $address->full_address,
                'coordinates' => $customerCoords
            ],
            'store_coordinates' => $storeCoords,
            'distance_km' => round($distance, 2),
            'delivery_options' => $options,
            'minimum_order' => $settings->minimum_order
        ];
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * Apply conditions like weather, peak hours, etc.
     */
    private function applyConditions($baseCost, $baseTime)
    {
        $adjustment = 1.0;

        // Peak hours (11:00-14:00, 18:00-21:00)
        $currentHour = (int) now()->format('H');
        if (($currentHour >= 11 && $currentHour <= 14) || ($currentHour >= 18 && $currentHour <= 21)) {
            $adjustment += 0.15; // +15% during peak hours
        }

        // Weekend adjustment
        if (now()->isWeekend()) {
            $adjustment += 0.1; // +10% on weekends
        }

        // Rain adjustment (you can integrate with weather API)
        // if ($this->isRaining()) {
        //     $adjustment += 0.2; // +20% when raining
        // }

        return [
            'cost' => ceil(($baseCost * $adjustment) / 500) * 500, // Round to nearest 500
            'time' => (int) ($baseTime * $adjustment)
        ];
    }

    /**
     * Validate minimum order for delivery
     */
    public function validateMinimumOrder($cartTotal)
    {
        $settings = DeliverySetting::getSettings();
        
        if ($cartTotal < $settings->minimum_order) {
            throw new Exception("Minimum order untuk delivery adalah Rp " . number_format($settings->minimum_order, 0, ',', '.'));
        }

        return true;
    }

    /**
     * Create delivery order (placeholder for future API integration)
     */
    public function createDeliveryOrder($provider, $transactionId, $addressId, $deliveryOption)
    {
        // This will be implemented when integrating with actual delivery APIs
        // For now, just return a mock tracking ID
        
        return [
            'success' => true,
            'tracking_id' => 'DS' . time() . rand(1000, 9999),
            'provider' => $provider,
            'estimated_delivery' => now()->addMinutes($deliveryOption['estimated_minutes']),
            'status' => 'confirmed'
        ];
    }
}