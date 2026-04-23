<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `is_instanced` flag to the buildings master table.
 *
 * Instanced buildings can have multiple physical copies per colony, each
 * tracked as a separate row in colony_buildings with a distinct instance_id.
 *
 * Buildings marked as instanced (per config/buildings.php):
 *   - housingComplex (id = 28)  — residential units, up to 6 per colony
 *   - hangar         (id = 44)  — ship slots, supply-limited
 *
 * Design context: GDD §4 (Instanced Buildings).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->boolean('is_instanced')->default(false)->after('max_level');
        });

        // Mark the two instanced building types.
        DB::table('buildings')->whereIn('id', [28, 44])->update(['is_instanced' => true]);
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('is_instanced');
        });
    }
};
