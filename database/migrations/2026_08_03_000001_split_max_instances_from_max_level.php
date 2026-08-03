<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the overloaded `max_level` field into `max_level` (per-instance level cap)
 * and `max_instances` (instance-count cap) for `is_instanced` buildings.
 *
 * Background (GDD §4c, Owner-Entscheidung 2026-08-03): `max_level` was doing double
 * duty for instanced buildings — used both as a level cap on a single instance
 * (AbstractTechnologyService::checkLevelUpLimit(), ColonyController::investBuilding())
 * and, separately, as an instance-count cap in the build-availability list
 * (ColonyController::buildableBuildings() — `$count >= $b->max_level`). For the
 * harvester this collided outright: "no level-up" (max_level=1) vs. "cap at 2
 * instances" cannot both be expressed in one field. `max_instances` is a new,
 * independent, nullable column (NULL = unbounded) so the two axes can be set
 * separately going forward.
 *
 * Wiring `max_instances` into ColonyController::buildableBuildings()/placeBuilding()
 * (replacing the `$b->max_level` read there) is deliberately NOT part of this
 * migration — that is controller logic, tracked separately (see GDD §4c / ROADMAP
 * Stufe 1c). This migration only introduces the column and backfills values that
 * preserve current runtime behaviour exactly.
 *
 * Backfill:
 *   - harvester (27): max_instances=2 (new hard cap, Owner-Entscheidung), max_level
 *     stays 1 (already set — no level-up path).
 *   - housingComplex (28): max_instances=6, carried over from the previous
 *     max_level=6 value (which is what buildableBuildings() was actually enforcing
 *     as an instance-count cap). max_level stays 6 — level is a live, independently
 *     tracked axis: ResourcesService::getSupplyBreakdown() sums per-instance
 *     `level` (not instance count) for the housing supply contribution, and
 *     ColonyController::investBuilding() enforces max_level as a real per-instance
 *     level cap via the `instance_id`-scoped invest endpoint. Nulling max_level here
 *     would silently uncap per-instance housing levels (and therefore supply).
 *   - hangar (44): no-op. max_level is already NULL (uncapped ship-class level per
 *     instance); max_instances is left NULL too (uncapped instance count, currently
 *     "repeatable, supply-limited" per config/buildings.php) since there is no prior
 *     value to carry over and no code currently enforces an instance cap for it.
 *   - all other (non-instanced) buildings: max_instances stays NULL, max_level
 *     unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('buildings', 'max_instances')) {
            Schema::table('buildings', function (Blueprint $table) {
                $table->integer('max_instances')->nullable()->after('max_level');
            });
        }

        DB::table('buildings')->where('id', 27)->update([
            'max_instances' => 2,
            'max_level' => 1,
        ]);

        DB::table('buildings')->where('id', 28)->update([
            'max_instances' => 6,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('buildings', 'max_instances')) {
            Schema::table('buildings', function (Blueprint $table) {
                $table->dropColumn('max_instances');
            });
        }
    }
};
