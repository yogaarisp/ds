<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Normal, Reseller, Keluarga
            $table->string('code')->unique(); // normal, reseller, keluarga
            $table->integer('adjustment')->default(0); // 0, -4000, -7000
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_categories');
    }
};
