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
        // 1. Master Coupons Table
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g., NEWUSER, IMLEK2026
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed'])->default('fixed'); // Tipe potongan: % atau Nominal
            $table->integer('value'); // Nilai potongan (misal 10000 atau 10 (persen))
            $table->integer('min_purchase')->default(0); // Minimal belanja
            $table->integer('max_discount')->nullable(); // Maksimal diskon (untuk tipe percentage)
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Customer Coupons Table (Pivot/Ownership)
        Schema::create('customer_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_coupons');
        Schema::dropIfExists('coupons');
    }
};
