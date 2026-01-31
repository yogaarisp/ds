<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // Buat 5 sample customers jika belum ada
        if (Customer::count() == 0) {
            Customer::create([
                'name' => 'Budi Santoso',
                'phone' => '08123456789',
                'email' => 'budi.santoso@email.com',
                'address' => 'Jl. Merdeka No. 123, Jakarta Pusat',
                'type' => 'regular',
                'level' => 'normal',
                'notes' => 'Pelanggan setia, suka pesan ayam original'
            ]);

            Customer::create([
                'name' => 'Siti Nurhaliza',
                'phone' => '08234567890',
                'email' => 'siti.nurhaliza@email.com',
                'address' => 'Jl. Sudirman No. 456, Jakarta Selatan',
                'type' => 'vip',
                'level' => 'reseller',
                'notes' => 'Reseller aktif, sering order dalam jumlah besar'
            ]);

            Customer::create([
                'name' => 'Ahmad Wijaya',
                'phone' => '08345678901',
                'email' => null,
                'address' => 'Jl. Gatot Subroto No. 789, Jakarta Barat',
                'type' => 'regular',
                'level' => 'keluarga',
                'notes' => 'Pelanggan keluarga, suka mix varian'
            ]);

            Customer::create([
                'name' => 'Dewi Lestari',
                'phone' => '08456789012',
                'email' => 'dewi.lestari@email.com',
                'address' => 'Jl. Thamrin No. 321, Jakarta Pusat',
                'type' => 'vip',
                'level' => 'reseller',
                'discount_percentage' => 15,
                'notes' => 'Reseller premium dengan diskon khusus 15%'
            ]);

            Customer::create([
                'name' => 'Rizki Pratama',
                'phone' => '08567890123',
                'email' => 'rizki.pratama@email.com',
                'address' => 'Jl. Kuningan No. 654, Jakarta Selatan',
                'type' => 'regular',
                'level' => 'normal',
                'notes' => 'Mahasiswa, pelanggan biasa'
            ]);
        }
    }
}
