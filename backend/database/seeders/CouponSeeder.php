<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Coupon;
use App\Models\Customer;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Master Coupons
        $coupons = [
            [
                'name' => 'Diskon Pengguna Baru',
                'code' => 'NEWUSER',
                'description' => 'Diskon spesial untuk pengguna baru',
                'type' => 'fixed',
                'value' => 15000,
                'min_purchase' => 50000,
                'start_date' => now(),
                'end_date' => now()->addMonths(1),
            ],
            [
                'name' => 'Diskon Imlek',
                'code' => 'IMLEK2026',
                'description' => 'Rayakan Imlek dengan diskon spesial',
                'type' => 'percentage',
                'value' => 10, // 10%
                'max_discount' => 20000,
                'min_purchase' => 100000,
                'start_date' => now(),
                'end_date' => now()->addWeeks(2),
            ],
            [
                'name' => 'Gratis Ongkir',
                'code' => 'FREESHIP',
                'description' => 'Gratis ongkir untuk pembelian minimal 150rb',
                'type' => 'fixed',
                'value' => 10000,
                'min_purchase' => 150000,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
            ],
        ];

        foreach ($coupons as $couponData) {
            Coupon::firstOrCreate(['code' => $couponData['code']], $couponData);
        }

        // 2. Assign Coupons to Existing Customers (Sample)
        $customers = Customer::limit(5)->get();
        $allCoupons = Coupon::all();

        foreach ($customers as $customer) {
            // Give random coupons to customers if they don't have them
            foreach ($allCoupons as $coupon) {
                if (!$customer->coupons()->where('coupon_id', $coupon->id)->exists()) {
                    // 50% chance to get a coupon
                    if (rand(0, 1)) {
                        $customer->coupons()->attach($coupon->id);
                    }
                }
            }
        }
    }
}
