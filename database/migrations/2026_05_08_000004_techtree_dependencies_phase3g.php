<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Techtree dependency updates — Phase 3g.
 *
 * 1. Rename building_hospital → building_infirmary (buildings.name).
 *
 * 2. Update building prerequisites:
 *    - bioFacility  (41): CC Lv1  → Harvester (27) Lv1
 *    - bar          (52): CC Lv1  → HousingComplex (28) Lv1
 *    - infirmary    (46): CC Lv3  → unchanged (no update needed, just rename)
 *    - hangar       (44): CC Lv3  → unchanged
 *
 * 3. Update ship prerequisites (Hangar level-gate):
 *    - drone    (85): Hangar Lv1 → unchanged
 *    - freighter(47): Hangar Lv1 → Hangar Lv2
 *    - corvette (37): Hangar Lv2 → Hangar Lv3
 *
 * 4. Add required_building2_id / required_building2_level to researches.
 *    Set second prerequisites for knowledge researches (90–96).
 *
 * 5. Add knowledge_cc_level_cap to config/game.php — handled in code, not DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // ── Step 1: Rename hospital → infirmary ──────────────────────────────

        DB::table('buildings')
            ->where('name', 'building_hospital')
            ->update(['name' => 'building_infirmary']);

        // ── Step 2: Update building prerequisites ─────────────────────────────

        // bioFacility (41): previously CC Lv1 → now Harvester (27) Lv1
        DB::table('buildings')
            ->where('id', 41)
            ->update(['required_building_id' => 27, 'required_building_level' => 1]);

        // bar (52): previously CC Lv1 → now HousingComplex (28) Lv1
        DB::table('buildings')
            ->where('id', 52)
            ->update(['required_building_id' => 28, 'required_building_level' => 1]);

        // ── Step 3: Update ship prerequisites (Hangar level-gate) ────────────

        // freighter (47): Hangar Lv1 → Hangar Lv2
        DB::table('ships')
            ->where('id', 47)
            ->update(['required_building_id' => 44, 'required_building_level' => 2]);

        // corvette (37): Hangar Lv2 → Hangar Lv3
        DB::table('ships')
            ->where('id', 37)
            ->update(['required_building_id' => 44, 'required_building_level' => 3]);

        // drone (85): Hangar Lv1 — already correct, no update needed.

        // ── Step 4: Add required_building2_id / required_building2_level ──────

        Schema::table('researches', function (Blueprint $table) {
            $table->integer('required_building2_id')->nullable()->after('required_building_level');
            $table->integer('required_building2_level')->nullable()->default(1)->after('required_building2_id');
        });

        // Set second prerequisites for knowledge researches (IDs 90–96):
        //
        // | ID | Name                    | req2_building_id | req2_building_level |
        // |----|-------------------------|------------------|----------------------|
        // | 90 | knowledge_construction  | NULL             | NULL                |
        // | 91 | knowledge_cartography   | 44 (hangar)      | 1                   |
        // | 92 | knowledge_geology       | 27 (harvester)   | 1                   |
        // | 93 | knowledge_agronomy      | 41 (bioFacility) | 1                   |
        // | 94 | knowledge_health        | 46 (infirmary)   | 1                   |
        // | 95 | knowledge_trade         | 52 (bar)         | 1                   |
        // | 96 | knowledge_defense       | 44 (hangar)      | 2                   |
        //
        // Also update primary prereq levels per the task spec:
        // | 92 | knowledge_geology  | req1: sciencelab Lv2 (was Lv1) |
        // | 96 | knowledge_defense  | req1: sciencelab Lv3 (was Lv2) |

        // knowledge_construction (90): no second prereq, no change to primary
        // (already has sciencelab Lv1 — leave as-is)

        // knowledge_cartography (91): hangar (44) Lv1 as second prereq
        DB::table('researches')
            ->where('id', 91)
            ->update([
                'required_building2_id'    => 44,
                'required_building2_level' => 1,
            ]);

        // knowledge_geology (92): harvester (27) Lv1 as second prereq; sciencelab Lv2 primary
        DB::table('researches')
            ->where('id', 92)
            ->update([
                'required_building_level'  => 2,
                'required_building2_id'    => 27,
                'required_building2_level' => 1,
            ]);

        // knowledge_agronomy (93): bioFacility (41) Lv1 as second prereq
        DB::table('researches')
            ->where('id', 93)
            ->update([
                'required_building2_id'    => 41,
                'required_building2_level' => 1,
            ]);

        // knowledge_health (94): infirmary (46) Lv1 as second prereq
        DB::table('researches')
            ->where('id', 94)
            ->update([
                'required_building2_id'    => 46,
                'required_building2_level' => 1,
            ]);

        // knowledge_trade (95): bar (52) Lv1 as second prereq
        DB::table('researches')
            ->where('id', 95)
            ->update([
                'required_building2_id'    => 52,
                'required_building2_level' => 1,
            ]);

        // knowledge_defense (96): hangar (44) Lv2 as second prereq; sciencelab Lv3 primary
        DB::table('researches')
            ->where('id', 96)
            ->update([
                'required_building_level'  => 3,
                'required_building2_id'    => 44,
                'required_building2_level' => 2,
            ]);

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // Reverse Step 1: rename infirmary → hospital
        DB::table('buildings')
            ->where('name', 'building_infirmary')
            ->update(['name' => 'building_hospital']);

        // Reverse Step 2: restore building prerequisites
        DB::table('buildings')
            ->where('id', 41)
            ->update(['required_building_id' => 25, 'required_building_level' => 1]);

        DB::table('buildings')
            ->where('id', 52)
            ->update(['required_building_id' => 25, 'required_building_level' => 1]);

        // Reverse Step 3: restore ship prerequisites
        DB::table('ships')
            ->where('id', 47)
            ->update(['required_building_level' => 1]);

        DB::table('ships')
            ->where('id', 37)
            ->update(['required_building_level' => 2]);

        // Reverse Step 4: restore knowledge primary prereq levels and remove columns
        DB::table('researches')
            ->where('id', 92)
            ->update(['required_building_level' => 1]);
        DB::table('researches')
            ->where('id', 96)
            ->update(['required_building_level' => 2]);

        Schema::table('researches', function (Blueprint $table) {
            $table->dropColumn(['required_building2_id', 'required_building2_level']);
        });

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
