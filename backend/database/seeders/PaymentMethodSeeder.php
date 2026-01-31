<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => 'Cash',
                'type' => 'cash',
                'is_active' => true,
            ],
            [
                'name' => 'QRIS',
                'type' => 'qris',
                'is_active' => true,
            ],
            [
                'name' => 'Transfer Bank Mandiri',
                'type' => 'transfer',
                'is_active' => true,
            ],
            [
                'name' => 'Transfer Bank BCA',
                'type' => 'transfer',
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['name' => $method['name']],
                [
                    'type' => $method['type'],
                    'is_active' => $method['is_active'],
                ]
            );
        }
    }
}
