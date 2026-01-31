<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained(); // Kasir
            $table->foreignId('price_category_id')->constrained();
            $table->enum('order_type', ['instant', 'scheduled']); // Order sekarang atau pre-order
            $table->date('scheduled_date')->nullable(); // Tanggal pre-order
            $table->time('scheduled_time')->nullable(); // Jam pre-order (opsional)
            $table->integer('subtotal');
            $table->integer('discount')->default(0);
            $table->integer('total');
            $table->integer('paid_amount')->default(0);
            $table->integer('change_amount')->default(0);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
