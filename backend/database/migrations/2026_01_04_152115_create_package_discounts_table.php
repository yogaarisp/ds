<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['reseller', 'keluarga']);
            $table->integer('discount_amount')->default(0);
            $table->timestamps();

            // Ensure unique discount per package per level
            $table->unique(['package_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_discounts');
    }
};
