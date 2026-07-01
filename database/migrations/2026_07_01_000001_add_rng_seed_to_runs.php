<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds rng_seed to runs — persisted per-run seed for deterministic randomness
 * once the Multiplayer Resolution Engine needs reproducible outcomes (ADR 0003).
 * Not yet consumed by any service.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->unsignedBigInteger('rng_seed')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->dropColumn('rng_seed');
        });
    }
};
