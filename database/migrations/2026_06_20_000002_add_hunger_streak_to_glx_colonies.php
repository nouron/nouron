<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds hunger_streak to glx_colonies (PR 2 — Organika provisioning sink).
 *
 * Counts consecutive Sols a colony's Organika stock failed to cover its food need.
 * Drives the escalating trust penalty (TrustService::hungerPenalty) and resets to 0
 * once the colony is fed again. A colony-level state, not a resource amount — hence
 * here rather than colony_resources (1 colony = 1 player).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->integer('hunger_streak')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->dropColumn('hunger_streak');
        });
    }
};
