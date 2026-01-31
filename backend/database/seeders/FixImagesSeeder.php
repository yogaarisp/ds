<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Setting;

class FixImagesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fix Product Images
        $productImages = [
            'AYM-ORI' => 'products/ayam-original-1767695471.jpg',
            'AYM-UDG' => 'products/ayam-udang-1767708223.jpg',
            'AYM-PDS' => 'products/ayam-pedas-1767695526.JPG',
            'AYM-KJU' => 'products/ayam-keju-1767695511.jpg',
            'KOL' => 'products/kol-1767708248.jpg',
            'UDG-UTH' => 'products/udang-utuh-1767708266.jpg',
            'UDG-PDS' => 'products/udang-utuh-pedas-1767708278.jpg',
        ];

        foreach ($productImages as $sku => $imagePath) {
            Product::where('sku', $sku)->update(['image' => $imagePath]);
        }

        // 2. Fix Store Logo
        Setting::set('store_logo', 'logos/tmNAETpu3MqFPBwzcedety7CPTePSx2MbiWHp52W.png');
        
        // 3. Fix Store Address/Phone (Optional, populate with dummy data if empty)
        if (!Setting::get('store_name')) {
            Setting::set('store_name', 'Dimsum Sarang');
        }
        if (!Setting::get('store_address')) {
            Setting::set('store_address', 'Jl. Contoh No. 123, Jakarta');
        }
        if (!Setting::get('store_phone')) {
            Setting::set('store_phone', '081234567890');
        }

        $this->command->info('Images and Settings fixed successfully.');
    }
}
