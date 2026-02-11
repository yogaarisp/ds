<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'finishing' status to tables enum for the 3-phase traffic light system.
     */
    public function up(): void
    {
        // MySQL: alter enum to add 'finishing'
        DB::statement("ALTER TABLE `tables` MODIFY COLUMN `status` ENUM('available','occupied','reserved','finishing') DEFAULT 'available'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First set any 'finishing' rows back to 'available'
        DB::table('tables')->where('status', 'finishing')->update(['status' => 'available']);
        DB::statement("ALTER TABLE `tables` MODIFY COLUMN `status` ENUM('available','occupied','reserved') DEFAULT 'available'");
    }
};
