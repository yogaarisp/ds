<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carousel_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');                      // Judul utama (text overlay)
            $table->string('subtitle')->nullable();        // Subtitle/tagline
            $table->string('period_text')->nullable();     // Text periode (e.g., "Periode Promo: 1-31 Januari 2026")
            $table->string('image');                       // Path gambar
            $table->string('badge_label')->nullable();     // Label voucher kecil (e.g., "Pengguna Baru")
            $table->string('badge_value')->nullable();     // Nilai voucher (e.g., "DISKON 15.000")
            $table->string('badge_label_2')->nullable();   // Label voucher kedua
            $table->string('badge_value_2')->nullable();   // Nilai voucher kedua
            $table->string('link_url')->nullable();        // Link tujuan jika di-klik
            $table->date('start_date')->nullable();        // Tanggal mulai tampil
            $table->date('end_date')->nullable();          // Tanggal berakhir
            $table->integer('sort_order')->default(0);     // Urutan tampil
            $table->boolean('is_active')->default(true);   // Status aktif
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carousel_slides');
    }
};
