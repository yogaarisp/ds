<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->enum('item_type', ['product', 'package']); // Produk satuan atau paket/mix
            $table->foreignId('product_id')->nullable()->constrained();
            $table->foreignId('package_id')->nullable()->constrained();
            $table->foreignId('package_combination_id')->nullable()->constrained();
            $table->integer('quantity'); // Jumlah item
            $table->integer('unit_price'); // Harga per item (sudah termasuk adjustment)
            $table->integer('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
