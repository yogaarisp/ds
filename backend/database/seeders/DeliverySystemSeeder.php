<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliverySystemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Insert Delivery Providers
        DB::table('delivery_providers')->insert([
            [
                'code' => 'internal',
                'name' => 'Kurir Toko',
                'type' => 'same_day',
                'is_active' => true,
                'priority' => 1,
                'base_cost' => 5000,
                'cost_per_km' => 2000,
                'max_distance' => 5,
                'base_time_minutes' => 30,
                'time_per_km_minutes' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'gosend',
                'name' => 'GoSend',
                'type' => 'instant',
                'is_active' => false, // Disabled until API configured
                'priority' => 2,
                'base_cost' => 8000,
                'cost_per_km' => 3000,
                'max_distance' => 15,
                'base_time_minutes' => 20,
                'time_per_km_minutes' => 3,
                'api_config' => json_encode([
                    'api_key' => null,
                    'base_url' => 'https://api.gojek.com/gosend/v1/',
                    'service_types' => ['instant', 'same_day']
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'grab',
                'name' => 'GrabExpress',
                'type' => 'instant',
                'is_active' => false,
                'priority' => 3,
                'base_cost' => 9000,
                'cost_per_km' => 3500,
                'max_distance' => 20,
                'base_time_minutes' => 25,
                'time_per_km_minutes' => 3,
                'api_config' => json_encode([
                    'client_id' => null,
                    'client_secret' => null,
                    'base_url' => 'https://partner-api.grab.com/grabexpress/v1/'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'anteraja',
                'name' => 'AnterAja',
                'type' => 'same_day',
                'is_active' => false,
                'priority' => 4,
                'base_cost' => 7000,
                'cost_per_km' => 2500,
                'max_distance' => 25,
                'base_time_minutes' => 60,
                'time_per_km_minutes' => 4,
                'api_config' => json_encode([
                    'api_key' => null,
                    'base_url' => 'https://api.anteraja.id/v1/'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // 2. Insert Default Delivery Settings
        DB::table('delivery_settings')->insert([
            'store_name' => 'Dimsum Sarang',
            'store_latitude' => -6.2088, // Jakarta coordinates (change to actual)
            'store_longitude' => 106.8456,
            'store_address' => 'Jl. Contoh No. 123, Jakarta',
            'minimum_order' => 25000,
            'is_delivery_active' => true,
            'operating_hours' => json_encode([
                'monday' => ['09:00', '21:00'],
                'tuesday' => ['09:00', '21:00'],
                'wednesday' => ['09:00', '21:00'],
                'thursday' => ['09:00', '21:00'],
                'friday' => ['09:00', '21:00'],
                'saturday' => ['09:00', '22:00'],
                'sunday' => ['10:00', '21:00']
            ]),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}