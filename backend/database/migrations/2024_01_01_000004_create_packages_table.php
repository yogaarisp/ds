<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Paket 2 pcs, Mix 2 Varian
            $table->string('code')->unique();
            $table->enum('type', ['bundle', 'mix']); // bundle = paket biasa, mix = kombinasi varian
            $table->integer('total_quantity'); // Total pcs dalam paket
            $table->integer('variant_count')->nullable(); // Jumlah varian untuk mix (2, 5, dll)
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
