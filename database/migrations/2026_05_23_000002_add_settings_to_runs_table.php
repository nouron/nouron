<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a settings column to the runs table.
 *
 * Stores a JSON snapshot of the game config at run creation time, e.g.:
 *   {"tick_limit":100,"bypass":{"ap_checks":false,...},"supply_cap_max":200}
 *
 * Nullable so existing runs (and runs created without explicit settings) remain valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->text('settings')->nullable()->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
