<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Geologische Instabilität (GDD §9) disrupts Harvester production for N Sols
 * without being a relocation — it must NOT reuse pending_until_tick, which
 * everywhere else means "currently relocating, tile change pending" and is
 * checked by ColonyController::placeBuilding() to reject a new relocation
 * attempt. Reusing it made instability lock the player out of the GDD's own
 * stated counter-play ("Relocation setzt Zähler zurück").
 *
 * instability_outage_until_tick is a separate, parallel field: while it is
 * >= current tick, the Harvester produces nothing (checked alongside
 * pending_until_tick in GameTick's harvester-yield generation), but a
 * relocation attempt is NOT blocked by it — only by pending_until_tick.
 * Relocating clears this field (ColonyController::placeBuilding), matching
 * the counter-play.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colony_buildings', function (Blueprint $table) {
            $table->integer('instability_outage_until_tick')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('colony_buildings', function (Blueprint $table) {
            $table->dropColumn('instability_outage_until_tick');
        });
    }
};
