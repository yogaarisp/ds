<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ayam, Udang, Tinwall Besar, dll
            $table->string('code')->unique();
            $table->enum('type', ['raw_material', 'packaging']); // Bahan baku atau packaging
            $table->string('unit'); // gram, pcs, lembar
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->integer('price_per_unit')->default(0); // Harga beli per unit
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
