<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('dp_amount')->default(0)->after('change_amount'); // Jumlah DP yang dibayar
            $table->integer('remaining_amount')->default(0)->after('dp_amount'); // Sisa yang harus dibayar
            $table->enum('payment_status', ['unpaid', 'dp', 'paid'])->default('unpaid')->after('remaining_amount');
            $table->timestamp('dp_paid_at')->nullable()->after('payment_status'); // Kapan DP dibayar
            $table->timestamp('fully_paid_at')->nullable()->after('dp_paid_at'); // Kapan lunas
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['dp_amount', 'remaining_amount', 'payment_status', 'dp_paid_at', 'fully_paid_at']);
        });
    }
};
