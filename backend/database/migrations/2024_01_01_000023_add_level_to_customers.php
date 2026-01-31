<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('level', ['normal', 'reseller', 'keluarga'])->default('normal')->after('type');
            $table->integer('discount_percentage')->default(0)->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['level', 'discount_percentage']);
        });
    }
};