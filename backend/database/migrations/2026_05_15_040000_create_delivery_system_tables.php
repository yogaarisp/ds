<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Delivery Providers (GoSend, Grab, AnterAja, etc)
        Schema::create('delivery_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // gosend, grab, anteraja, etc
            $table->string('name'); // GoSend, GrabExpress, etc
            $table->string('logo_url')->nullable();
            $table->enum('type', ['instant', 'same_day', 'next_day']);
            $table->json('api_config')->nullable(); // API credentials & settings
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Display order
            $table->decimal('base_cost', 10, 2)->default(0); // Base delivery cost
            $table->decimal('cost_per_km', 8, 2)->default(0); // Cost per kilometer
            $table->decimal('max_distance', 8, 2)->default(10); // Max delivery distance (km)
            $table->integer('base_time_minutes')->default(30); // Base delivery time
            $table->integer('time_per_km_minutes')->default(3); // Additional time per km
            $table->timestamps();
        });

        // 2. Delivery Settings (Store location & general settings)
        Schema::create('delivery_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name');
            $table->decimal('store_latitude', 10, 8);
            $table->decimal('store_longitude', 11, 8);
            $table->string('store_address');
            $table->decimal('minimum_order', 10, 2)->default(25000); // Min order for delivery
            $table->boolean('is_delivery_active')->default(true);
            $table->json('operating_hours')->nullable(); // Delivery hours per day
            $table->timestamps();
        });

        // 3. Delivery Quotes Cache
        Schema::create('delivery_quotes', function (Blueprint $table) {
            $table->id();
            $table->string('provider_code');
            $table->decimal('origin_lat', 10, 8);
            $table->decimal('origin_lng', 11, 8);
            $table->decimal('dest_lat', 10, 8);
            $table->decimal('dest_lng', 11, 8);
            $table->decimal('cost', 10, 2);
            $table->integer('estimated_minutes');
            $table->string('service_type')->nullable();
            $table->json('provider_response')->nullable(); // Raw API response
            $table->timestamp('expires_at');
            $table->timestamps();
            
            // Index for quick lookup
            $table->index(['provider_code', 'origin_lat', 'origin_lng', 'dest_lat', 'dest_lng']);
        });

        // 4. Add delivery fields to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('delivery_address_id')->nullable()->constrained('customer_addresses')->onDelete('set null');
            $table->string('delivery_provider')->nullable(); // gosend, grab, etc
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('estimated_delivery_minutes')->nullable();
            $table->enum('delivery_type', ['pickup', 'delivery'])->default('pickup');
            $table->string('delivery_tracking_id')->nullable();
            $table->enum('delivery_status', ['pending', 'confirmed', 'picked_up', 'on_the_way', 'delivered', 'cancelled'])->nullable();
            $table->json('delivery_details')->nullable(); // Provider-specific data
            $table->timestamp('delivery_confirmed_at')->nullable();
            $table->timestamp('delivery_picked_up_at')->nullable();
            $table->timestamp('delivery_delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['delivery_address_id']);
            $table->dropColumn([
                'delivery_address_id',
                'delivery_provider',
                'shipping_cost',
                'distance_km',
                'estimated_delivery_minutes',
                'delivery_type',
                'delivery_tracking_id',
                'delivery_status',
                'delivery_details',
                'delivery_confirmed_at',
                'delivery_picked_up_at',
                'delivery_delivered_at'
            ]);
        });
        
        Schema::dropIfExists('delivery_quotes');
        Schema::dropIfExists('delivery_settings');
        Schema::dropIfExists('delivery_providers');
    }
};