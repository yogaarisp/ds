<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add default coordinate settings to the settings table
        $defaultSettings = [
            ['key' => 'store_latitude', 'value' => '-6.2088', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'store_longitude', 'value' => '106.8456', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($defaultSettings as $setting) {
            // Only insert if the key doesn't already exist
            $exists = DB::table('settings')->where('key', $setting['key'])->exists();
            if (!$exists) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    public function down(): void
    {
        // Remove the coordinate settings
        DB::table('settings')->whereIn('key', ['store_latitude', 'store_longitude'])->delete();
    }
};