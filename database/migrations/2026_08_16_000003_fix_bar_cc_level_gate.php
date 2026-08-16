<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bug fix (Owner playtest, 2026-08-16): bar (52, Cantina) has been available
 * from CC Lv1 since migration 2026_05_08_000004_techtree_dependencies_phase3g.php
 * deliberately regated it to housingComplex (28) Lv1. When hangar's own CC-gate
 * was later lowered from Lv3 to Lv2 in migration
 * 2026_06_25_000100_add_placed_at_tick_and_path_gate_stammdaten.php to unify
 * all three path buildings (sciencelab/hangar/bar) at CC Lv2, bar's row was
 * never updated to match — it kept bypassing the CC-Lv2 gate its two siblings
 * enforce (ColonyController::PATH_BUILDING_IDS docblock, GDD §13 "Pfadwahl ab
 * Sol 3").
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('buildings')->where('id', 52)->update([
            'required_building_id' => 25,   // CommandCenter
            'required_building_level' => 2,
        ]);
    }

    public function down(): void
    {
        DB::table('buildings')->where('id', 52)->update([
            'required_building_id' => 28,   // housingComplex — pre-fix value
            'required_building_level' => 1,
        ]);
    }
};
