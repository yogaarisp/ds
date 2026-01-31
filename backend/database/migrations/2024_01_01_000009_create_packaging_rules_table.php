<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aturan pemakaian packaging otomatis
        Schema::create('packaging_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->onDelete('cascade');
            $table->enum('rule_type', ['per_quantity', 'per_order']); // per_quantity = per X pcs, per_order = per transaksi
            $table->integer('trigger_quantity')->nullable(); // Trigger setiap X pcs (untuk per_quantity)
            $table->decimal('usage_quantity', 10, 2); // Jumlah packaging yang dipakai
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_rules');
    }
};
