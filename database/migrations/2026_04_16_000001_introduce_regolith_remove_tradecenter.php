<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Introduces Regolith as the primary local mining resource (replaces res_water, ID 3).
 * Removes Tradecenter (building ID 43) — trade now happens at the Bar (ID 52).
 *
 * Design context: GDD §3 (Ressourcen), §4 (Gebäude), §11 (Handel)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Resource 3: water → Regolith ─────────────────────────────────────
        DB::table('resources')->where('id', 3)->update([
            'name'         => 'res_regolith',
            'abbreviation' => 'Rg',
            'start_amount' => 200,
            'icon'         => 'resicon-regolith',
        ]);

        // ── Tradecenter (ID 43): remove prerequisite dependencies ─────────────
        // Trader advisor and economicScience research previously required Tradecenter.
        // They now require Bar (ID 52) — the new trade hub.
        DB::table('personell')->where('id', 92)->update(['required_building_id' => 52]);
        DB::table('researches')->where('id', 76)->update(['required_building_id' => 52]);

        // ── Remove Tradecenter building ────────────────────────────────────────
        DB::table('building_costs')->where('building_id', 43)->delete();
        DB::table('colony_buildings')->where('building_id', 43)->delete();
        DB::table('buildings')->where('id', 43)->delete();
    }

    public function down(): void
    {
        // Restore resource 3 to res_water
        DB::table('resources')->where('id', 3)->update([
            'name'         => 'res_water',
            'abbreviation' => 'W',
            'start_amount' => 500,
            'icon'         => 'resicon-water',
        ]);

        // Restore Tradecenter prerequisites
        DB::table('personell')->where('id', 92)->update(['required_building_id' => 43]);
        DB::table('researches')->where('id', 76)->update(['required_building_id' => 43]);

        // Note: tradecenter building row, costs and colony instances are not restored
        // (destructive removal — use a DB snapshot if full rollback is needed)
    }
};
