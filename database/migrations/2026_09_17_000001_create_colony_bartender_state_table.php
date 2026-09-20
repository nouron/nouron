<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the colony_bartender_state table (Cantina-Barkeeper Tomas, GDD §12, A40).
 *
 * One row per colony, created lazily on first interaction. Tracks the
 * cumulative "Mit Tomas reden" interaction count for the whole run (never
 * reset per tick) and the tick of the most recent interaction, used to
 * enforce the once-per-tick cooldown.
 *
 * Columns:
 *   colony_id             — primary key, one row per colony
 *   interaction_count     — cumulative talks with Tomas, persists for the run
 *   last_interaction_tick — tick of the most recent interaction (cooldown gate)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_bartender_state', function (Blueprint $table) {
            $table->unsignedInteger('colony_id')->primary();
            $table->unsignedInteger('interaction_count')->default(0);
            $table->unsignedInteger('last_interaction_tick')->nullable();

            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_bartender_state');
    }
};
