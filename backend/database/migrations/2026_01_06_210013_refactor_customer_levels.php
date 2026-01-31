<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Default Customer Types
        $types = [
            ['name' => 'Biasa', 'description' => 'Pelanggan Regular'],
            ['name' => 'Reseller', 'description' => 'Pelanggan Reseller'],
            ['name' => 'Keluarga', 'description' => 'Pelanggan Keluarga'],
        ];

        foreach ($types as $type) {
            DB::table('customer_types')->updateOrInsert(
                ['name' => $type['name']],
                ['description' => $type['description']]
            );
        }

        $biasaId = DB::table('customer_types')->where('name', 'Biasa')->value('id');
        $resellerId = DB::table('customer_types')->where('name', 'Reseller')->value('id');
        $keluargaId = DB::table('customer_types')->where('name', 'Keluarga')->value('id');

        // 2. Modify Customers Table
        if (!Schema::hasColumn('customers', 'customer_type_id')) {
            Schema::table('customers', function (Blueprint $table) use ($biasaId) {
                $table->foreignId('customer_type_id')->nullable()->after('type')->constrained('customer_types');
            });
        }

        // Migrate Data
        if (Schema::hasColumn('customers', 'level')) {
            DB::table('customers')->where('level', 'normal')->update(['customer_type_id' => $biasaId]);
            DB::table('customers')->where('level', 'reseller')->update(['customer_type_id' => $resellerId]);
            DB::table('customers')->where('level', 'keluarga')->update(['customer_type_id' => $keluargaId]);
        }
        
        // Handle cases where level might be null or unknown (set to Biasa)
        DB::table('customers')->whereNull('customer_type_id')->update(['customer_type_id' => $biasaId]);

        if (Schema::hasColumn('customers', 'level')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('level');
                // Make customer_type_id non-nullable now that data is filled
                $table->unsignedBigInteger('customer_type_id')->nullable(false)->change();
            });
        }

        // 3. Modify Package Discounts Table
        // Need to drop unique constraint first because we are changing schema
        if (!Schema::hasColumn('package_discounts', 'customer_type_id')) {
            Schema::table('package_discounts', function (Blueprint $table) {
                // Drop FK first to allow dropping the index it might rely on
                $table->dropForeign(['package_id']);
                
                // Check if index exists using driver-specific SQL
                $indexExists = false;
                if (DB::getDriverName() === 'sqlite') {
                    $result = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name='package_discounts_package_id_level_unique'");
                    $indexExists = count($result) > 0;
                } else {
                    // MySQL/MariaDB
                    $result = DB::select("SHOW INDEX FROM package_discounts WHERE Key_name = 'package_discounts_package_id_level_unique'");
                    $indexExists = count($result) > 0;
                }
                
                if ($indexExists) {
                   $table->dropUnique(['package_id', 'level']);
                }
                
                $table->foreignId('customer_type_id')->nullable()->after('package_id')->constrained('customer_types')->cascadeOnDelete();
                
                // Re-add FK for package_id
                $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
            });
        }

        // Migrate Data
        // Mapping old enum values to IDs
        $mapping = [
            'reseller' => $resellerId,
            'keluarga' => $keluargaId
            // 'normal' usually doesn't have discounts in this table, but if present
        ];

        if (Schema::hasColumn('package_discounts', 'level')) {
            foreach ($mapping as $level => $id) {
                 DB::table('package_discounts')->where('level', $level)->update(['customer_type_id' => $id]);
            }
        }

        // Delete any discounts that couldn't be mapped (orphan data)
        DB::table('package_discounts')->whereNull('customer_type_id')->delete();

        Schema::table('package_discounts', function (Blueprint $table) {
            $table->dropColumn('level');
            $table->unsignedBigInteger('customer_type_id')->nullable(false)->change();
            $table->unique(['package_id', 'customer_type_id']);
        });
    }

    public function down(): void
    {
        // Revert Customers Table
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('level', ['normal', 'reseller', 'keluarga'])->default('normal')->after('type');
        });

        // Restore Data (Best Effort)
        $biasaId = DB::table('customer_types')->where('name', 'Biasa')->value('id');
        $resellerId = DB::table('customer_types')->where('name', 'Reseller')->value('id');
        $keluargaId = DB::table('customer_types')->where('name', 'Keluarga')->value('id');
        
        DB::table('customers')->where('customer_type_id', $biasaId)->update(['level' => 'normal']);
        DB::table('customers')->where('customer_type_id', $resellerId)->update(['level' => 'reseller']);
        DB::table('customers')->where('customer_type_id', $keluargaId)->update(['level' => 'keluarga']);

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['customer_type_id']);
            $table->dropColumn('customer_type_id');
        });

        // Revert Package Discounts Table
        Schema::table('package_discounts', function (Blueprint $table) {
            $table->dropForeign(['customer_type_id']);
            $table->dropUnique(['package_id', 'customer_type_id']);
            $table->enum('level', ['reseller', 'keluarga'])->after('package_id'); // We lose 'normal' if it was there? No, schema was ['reseller', 'keluarga']
        });
        
        // Restore Data (Best Effort)
        DB::table('package_discounts')->where('customer_type_id', $resellerId)->update(['level' => 'reseller']);
        DB::table('package_discounts')->where('customer_type_id', $keluargaId)->update(['level' => 'keluarga']);
        // Any other types will be lost/deleted or cause error if not nullable. For now assuming only these 2 existed.
        DB::table('package_discounts')->whereNull('level')->delete();

        Schema::table('package_discounts', function (Blueprint $table) {
           $table->dropColumn('customer_type_id');
           $table->unique(['package_id', 'level']);
        });

        // Delete customer types
        DB::table('customer_types')->whereIn('name', ['Biasa', 'Reseller', 'Keluarga'])->delete();
    }
};
