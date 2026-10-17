<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combination_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_combination_id')
                ->constrained('package_combinations')
                ->cascadeOnDelete();
            $table->foreignId('customer_type_id')
                ->constrained('customer_types')
                ->cascadeOnDelete();
            $table->integer('discount_amount')->default(0);
            $table->timestamps();

            // Satu baris per kombinasi per tipe customer
            $table->unique(['package_combination_id', 'customer_type_id'], 'combo_discount_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combination_discounts');
    }
};
