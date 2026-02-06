<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tables')) {
            Schema::create('tables', function (Blueprint $table) {
                $table->id();
                $table->string('name');           // "Meja 1", "Meja 2", dll
                $table->string('code')->unique(); // "table-1", "table-2" (untuk URL)
                $table->enum('status', ['available', 'occupied', 'reserved'])->default('available');
                $table->integer('capacity')->default(4);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
