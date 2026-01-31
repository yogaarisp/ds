<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Package;
use App\Models\PackageCombination;
use App\Models\InventoryItem;
use App\Models\Recipe;
use App\Models\PackagingRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        User::create([
            'name' => 'Owner Dimsum',
            'email' => 'owner@dimsum.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
        ]);

        User::create([
            'name' => 'Kasir 1',
            'email' => 'kasir@dimsum.com',
            'password' => Hash::make('password'),
            'role' => 'kasir',
        ]);

        User::create([
            'name' => 'Staff Gudang',
            'email' => 'gudang@dimsum.com',
            'password' => Hash::make('password'),
            'role' => 'gudang',
        ]);

        // Get Customer Types (already created by migration)
        $typeBiasa = \App\Models\CustomerType::where('name', 'Biasa')->first();
        $typeReseller = \App\Models\CustomerType::where('name', 'Reseller')->first();
        $typeKeluarga = \App\Models\CustomerType::where('name', 'Keluarga')->first();

        // Customers
        Customer::create([
            'name' => 'Budi Santoso',
            'phone' => '08123456789',
            'email' => 'budi.santoso@email.com',
            'address' => 'Jl. Merdeka No. 123, Jakarta Pusat',
            'customer_type_id' => $typeBiasa->id,
            'notes' => 'Pelanggan setia, suka pesan ayam original'
        ]);

        Customer::create([
            'name' => 'Siti Nurhaliza',
            'phone' => '08234567890',
            'email' => 'siti.nurhaliza@email.com',
            'address' => 'Jl. Sudirman No. 456, Jakarta Selatan',
            'customer_type_id' => $typeReseller->id,
            'notes' => 'VIP customer, sering order dalam jumlah besar'
        ]);

        Customer::create([
            'name' => 'Ahmad Wijaya',
            'phone' => '08345678901',
            'email' => null,
            'address' => 'Jl. Gatot Subroto No. 789, Jakarta Barat',
            'customer_type_id' => $typeKeluarga->id,
            'notes' => 'Suka mix varian, biasanya pre-order'
        ]);

        Customer::create([
            'name' => 'Dewi Lestari',
            'phone' => '08456789012',
            'email' => 'dewi.lestari@email.com',
            'address' => 'Jl. Thamrin No. 321, Jakarta Pusat',
            'customer_type_id' => $typeReseller->id,
            'notes' => 'Pelanggan korporat, sering order untuk acara kantor'
        ]);

        Customer::create([
            'name' => 'Rizki Pratama',
            'phone' => '08567890123',
            'email' => 'rizki.pratama@email.com',
            'address' => 'Jl. Kuningan No. 654, Jakarta Selatan',
            'customer_type_id' => $typeBiasa->id,
            'notes' => 'Mahasiswa, sering order paket hemat'
        ]);

        // Products
        $products = [
            ['name' => 'Ayam Original', 'sku' => 'AYM-ORI', 'base_price' => 4000, 'stock' => 100],
            ['name' => 'Ayam Udang', 'sku' => 'AYM-UDG', 'base_price' => 4500, 'stock' => 100],
            ['name' => 'Ayam Pedas', 'sku' => 'AYM-PDS', 'base_price' => 4000, 'stock' => 100],
            ['name' => 'Ayam Keju', 'sku' => 'AYM-KJU', 'base_price' => 4500, 'stock' => 100],
            ['name' => 'Nori', 'sku' => 'NORI', 'base_price' => 5000, 'stock' => 50],
            ['name' => 'Gyoza', 'sku' => 'GYOZA', 'base_price' => 5000, 'stock' => 50],
            ['name' => 'Kol', 'sku' => 'KOL', 'base_price' => 3500, 'stock' => 80],
            ['name' => 'Udang Utuh', 'sku' => 'UDG-UTH', 'base_price' => 6000, 'stock' => 50],
            ['name' => 'Udang Utuh Pedas', 'sku' => 'UDG-PDS', 'base_price' => 6000, 'stock' => 50],
            ['name' => 'Ciraso', 'sku' => 'CIRASO', 'base_price' => 4000, 'stock' => 60],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Inventory Items - Bahan Baku
        $rawMaterials = [
            ['name' => 'Daging Ayam', 'code' => 'BB-AYAM', 'type' => 'raw_material', 'unit' => 'gram', 'stock' => 10000, 'min_stock' => 2000],
            ['name' => 'Udang', 'code' => 'BB-UDANG', 'type' => 'raw_material', 'unit' => 'gram', 'stock' => 5000, 'min_stock' => 1000],
            ['name' => 'Kulit Dimsum', 'code' => 'BB-KULIT', 'type' => 'raw_material', 'unit' => 'pcs', 'stock' => 500, 'min_stock' => 100],
            ['name' => 'Keju', 'code' => 'BB-KEJU', 'type' => 'raw_material', 'unit' => 'gram', 'stock' => 2000, 'min_stock' => 500],
            ['name' => 'Nori Sheet', 'code' => 'BB-NORI', 'type' => 'raw_material', 'unit' => 'lembar', 'stock' => 200, 'min_stock' => 50],
        ];

        foreach ($rawMaterials as $item) {
            InventoryItem::create($item);
        }

        // Inventory Items - Packaging
        $packaging = [
            ['name' => 'Tinwall Besar', 'code' => 'PKG-TW-B', 'type' => 'packaging', 'unit' => 'pcs', 'stock' => 200, 'min_stock' => 50],
            ['name' => 'Tinwall Kecil', 'code' => 'PKG-TW-K', 'type' => 'packaging', 'unit' => 'pcs', 'stock' => 200, 'min_stock' => 50],
            ['name' => 'Plastik Besar', 'code' => 'PKG-PL-B', 'type' => 'packaging', 'unit' => 'pcs', 'stock' => 300, 'min_stock' => 100],
            ['name' => 'Plastik Kecil', 'code' => 'PKG-PL-K', 'type' => 'packaging', 'unit' => 'pcs', 'stock' => 300, 'min_stock' => 100],
            ['name' => 'Sumpit', 'code' => 'PKG-SUMPIT', 'type' => 'packaging', 'unit' => 'pcs', 'stock' => 500, 'min_stock' => 100],
            ['name' => 'Sendok', 'code' => 'PKG-SENDOK', 'type' => 'packaging', 'unit' => 'pcs', 'stock' => 500, 'min_stock' => 100],
            ['name' => 'Tisu', 'code' => 'PKG-TISU', 'type' => 'packaging', 'unit' => 'pcs', 'stock' => 1000, 'min_stock' => 200],
        ];

        foreach ($packaging as $item) {
            InventoryItem::create($item);
        }

        // Recipes (contoh untuk Ayam Original)
        $ayamOriginal = Product::where('sku', 'AYM-ORI')->first();
        $dagingAyam = InventoryItem::where('code', 'BB-AYAM')->first();
        $kulitDimsum = InventoryItem::where('code', 'BB-KULIT')->first();

        Recipe::create(['product_id' => $ayamOriginal->id, 'inventory_item_id' => $dagingAyam->id, 'quantity' => 25]);
        Recipe::create(['product_id' => $ayamOriginal->id, 'inventory_item_id' => $kulitDimsum->id, 'quantity' => 1]);

        // Packages
        $paket2 = Package::create(['name' => 'Paket 2 pcs', 'code' => 'PKT-2', 'type' => 'bundle', 'total_quantity' => 2]);
        $paket5 = Package::create(['name' => 'Paket 5 pcs', 'code' => 'PKT-5', 'type' => 'bundle', 'total_quantity' => 5]);
        $paket10 = Package::create(['name' => 'Paket 10 pcs', 'code' => 'PKT-10', 'type' => 'bundle', 'total_quantity' => 10]);
        $mix2 = Package::create(['name' => 'Mix 2 Varian', 'code' => 'MIX-2', 'type' => 'mix', 'total_quantity' => 10, 'variant_count' => 2]);
        $mix5 = Package::create(['name' => 'Mix 5 Varian', 'code' => 'MIX-5', 'type' => 'mix', 'total_quantity' => 10, 'variant_count' => 5]);

        // Mix 2 Varian Combinations
        $ayamOri = Product::where('sku', 'AYM-ORI')->first();
        $ayamUdang = Product::where('sku', 'AYM-UDG')->first();
        $ayamKeju = Product::where('sku', 'AYM-KJU')->first();
        $ayamPedas = Product::where('sku', 'AYM-PDS')->first();
        $udangUtuh = Product::where('sku', 'UDG-UTH')->first();

        // Ayam + Udang = 34.000
        $comb1 = PackageCombination::create(['package_id' => $mix2->id, 'name' => 'Ayam Original + Ayam Udang', 'base_price' => 34000]);
        $comb1->items()->create(['product_id' => $ayamOri->id, 'quantity' => 5]);
        $comb1->items()->create(['product_id' => $ayamUdang->id, 'quantity' => 5]);

        // Ayam + Keju = 34.000
        $comb2 = PackageCombination::create(['package_id' => $mix2->id, 'name' => 'Ayam Original + Ayam Keju', 'base_price' => 34000]);
        $comb2->items()->create(['product_id' => $ayamOri->id, 'quantity' => 5]);
        $comb2->items()->create(['product_id' => $ayamKeju->id, 'quantity' => 5]);

        // Udang + Keju = 35.000
        $comb3 = PackageCombination::create(['package_id' => $mix2->id, 'name' => 'Ayam Udang + Ayam Keju', 'base_price' => 35000]);
        $comb3->items()->create(['product_id' => $ayamUdang->id, 'quantity' => 5]);
        $comb3->items()->create(['product_id' => $ayamKeju->id, 'quantity' => 5]);

        // Pedas + Keju = 35.000
        $comb4 = PackageCombination::create(['package_id' => $mix2->id, 'name' => 'Ayam Pedas + Ayam Keju', 'base_price' => 35000]);
        $comb4->items()->create(['product_id' => $ayamPedas->id, 'quantity' => 5]);
        $comb4->items()->create(['product_id' => $ayamKeju->id, 'quantity' => 5]);

        // Packaging Rules
        $tinwallBesar = InventoryItem::where('code', 'PKG-TW-B')->first();
        $tinwallKecil = InventoryItem::where('code', 'PKG-TW-K')->first();
        $sumpit = InventoryItem::where('code', 'PKG-SUMPIT')->first();
        $tisu = InventoryItem::where('code', 'PKG-TISU')->first();

        PackagingRule::create([
            'inventory_item_id' => $tinwallBesar->id,
            'rule_type' => 'per_quantity',
            'trigger_quantity' => 10,
            'usage_quantity' => 1,
            'description' => 'Tinwall besar per 10 pcs',
        ]);

        PackagingRule::create([
            'inventory_item_id' => $tinwallKecil->id,
            'rule_type' => 'per_quantity',
            'trigger_quantity' => 5,
            'usage_quantity' => 1,
            'description' => 'Tinwall kecil per 5 pcs',
        ]);

        PackagingRule::create([
            'inventory_item_id' => $sumpit->id,
            'rule_type' => 'per_order',
            'trigger_quantity' => null,
            'usage_quantity' => 2,
            'description' => 'Sumpit per order',
        ]);

        PackagingRule::create([
            'inventory_item_id' => $tisu->id,
            'rule_type' => 'per_order',
            'trigger_quantity' => null,
            'usage_quantity' => 2,
            'description' => 'Tisu per order',
        ]);
    }
}
