<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign key constraint and column from transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['price_category_id']);
            $table->dropColumn('price_category_id');
        });

        // Drop price_categories table
        Schema::dropIfExists('price_categories');
    }

    public function down(): void
    {
        // Recreate price_categories table
        Schema::create('price_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('adjustment')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add back price_category_id column to transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('price_category_id')->after('user_id')->constrained();
        });
    }
};