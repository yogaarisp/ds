<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Detail produk dalam setiap kombinasi
        Schema::create('package_combination_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_combination_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity'); // Jumlah pcs produk ini dalam kombinasi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_combination_items');
    }
};
