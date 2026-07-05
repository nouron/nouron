<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a nullable target column to colony_hangar_missions (mission catalog, GDD §8b).
 *
 * JSON payload, shape depends on the mission's target_type:
 *   signal_tile / ruin_tile — {"q": int, "r": int}
 *   knowledge               — {"research_id": int}
 *
 * destination now carries the mission_key (was free text before the catalog).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colony_hangar_missions', function (Blueprint $table) {
            $table->json('target')->nullable()->after('sol_distance');
        });
    }

    public function down(): void
    {
        Schema::table('colony_hangar_missions', function (Blueprint $table) {
            $table->dropColumn('target');
        });
    }
};
