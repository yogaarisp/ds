<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change enum type to include 'ewallet'
        DB::statement("ALTER TABLE payment_methods MODIFY COLUMN type ENUM('cash', 'transfer', 'qris', 'ewallet', 'other') NOT NULL DEFAULT 'other'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original enum
        DB::statement("ALTER TABLE payment_methods MODIFY COLUMN type ENUM('cash', 'transfer', 'qris', 'other') NOT NULL DEFAULT 'other'");
    }
};
