<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Techtree redesign — GDD §11 (Phase 3 / new Singleplayer Roguelike design).
 *
 * Changes applied:
 *
 * 1. Add is_active (TINYINT DEFAULT 1) to buildings, researches, ships, personell.
 *    Inactive entities are hidden from the techtree UI but kept for data integrity.
 *
 * 2. Mark legacy entities inactive:
 *    Researches: techs_biology(33), techs_languages(34), techs_mathematics(39),
 *      techs_medicalScience(72), techs_physics(73), techs_chemistry(74),
 *      techs_economicScience(76), techs_diplomacy(79), techs_politicalScience(80),
 *      techs_military(81)
 *    Ships: techs_frigate1(29), techs_battlecruiser1(49),
 *      techs_mediumTransporter(83), techs_largeTransporter(84)
 *    Buildings: all 11 remaining buildings stay active (is_active=1).
 *    Personell: all 5 stay active (is_active=1).
 *
 * 3. Update grid coordinates (row/column) for all active entities per GDD §11.3.
 *    The unique(row,column) constraint requires careful ordering — conflicting rows
 *    are moved sequentially to avoid transient duplicates.
 *
 * 4. Update building prerequisites to match GDD §11.2 CC-tier rules.
 *
 * 5. Set required_building_id / required_building_level on knowledge researches
 *    (IDs 90–96): all require sciencelab (ID 31) Lv1; knowledge_defense (96) Lv2.
 *
 * 6. Update grid coordinates for knowledge researches: col 6 → col 4.
 *
 * 7. Update personell prerequisites and grid coordinates.
 */
return new class extends Migration
{
    // Legacy researches to deactivate
    private const INACTIVE_RESEARCHES = [33, 34, 39, 72, 73, 74, 76, 79, 80, 81];

    // Legacy ships to deactivate
    private const INACTIVE_SHIPS = [29, 49, 83, 84];

    public function up(): void
    {
        // Disable FK checks — buildings/researches reference each other, and
        // on migrate:fresh master-data rows may not exist yet (they come from
        // testdata.sqlite.sql, not migrations).
        DB::statement('PRAGMA foreign_keys = OFF');

        // ── Step 1: Add is_active column to all four master tables ───────────

        Schema::table('buildings', function (Blueprint $table) {
            $table->tinyInteger('is_active')->default(1)->after('is_instanced');
        });

        Schema::table('researches', function (Blueprint $table) {
            $table->tinyInteger('is_active')->default(1)->after('supply_cost');
        });

        Schema::table('ships', function (Blueprint $table) {
            $table->tinyInteger('is_active')->default(1)->after('supply_cost');
        });

        Schema::table('personell', function (Blueprint $table) {
            $table->tinyInteger('is_active')->default(1)->after('max_status_points');
        });

        // ── Step 2: Mark legacy entities inactive ────────────────────────────

        DB::table('researches')
            ->whereIn('id', self::INACTIVE_RESEARCHES)
            ->update(['is_active' => 0]);

        DB::table('ships')
            ->whereIn('id', self::INACTIVE_SHIPS)
            ->update(['is_active' => 0]);

        // ── Step 3: Update building grid coordinates ─────────────────────────
        //
        // Current → Target (must resolve unique(row,column) conflicts in order):
        //   52 bar:           (2,1) → (4,3)  [move first — clears (2,1) for harvester]
        //   27 harvester:     (1,2) → (2,1)  [52 has left (2,1)]
        //   32 temple:        (3,1) → (7,3)  [move before bioFacility needs (3,1)]
        //   41 bioFacility:   (1,3) → (3,1)  [32 has left (3,1)]
        //   31 sciencelab:    (2,2) → (4,2)  [free target]
        //   30 depot:         (2,5) → (5,2)  [free target]
        //   44 hangar:        (2,3) → (6,3)  [free target]
        //   46 hospital:      (2,4) → (5,3)  [free target]
        //   50 denkmal:       (3,2) → (8,3)  [free target]
        //   25 commandCenter: (0,2) → (0,2)  [no change]
        //   28 housingComplex:(1,1) → (1,1)  [no change]

        DB::table('buildings')->where('id', 52)->update(['row' => 4, 'column' => 3]);
        DB::table('buildings')->where('id', 27)->update(['row' => 2, 'column' => 1]);
        DB::table('buildings')->where('id', 32)->update(['row' => 7, 'column' => 3]);
        DB::table('buildings')->where('id', 41)->update(['row' => 3, 'column' => 1]);
        DB::table('buildings')->where('id', 31)->update(['row' => 4, 'column' => 2]);
        DB::table('buildings')->where('id', 30)->update(['row' => 5, 'column' => 2]);
        DB::table('buildings')->where('id', 44)->update(['row' => 6, 'column' => 3]);
        DB::table('buildings')->where('id', 46)->update(['row' => 5, 'column' => 3]);
        DB::table('buildings')->where('id', 50)->update(['row' => 8, 'column' => 3]);

        // ── Step 4: Update building prerequisites per GDD §11.2 ─────────────

        // depot: CC Lv1 → CC Lv2
        DB::table('buildings')->where('id', 30)->update(['required_building_level' => 2]);
        // sciencelab: CC Lv4 → CC Lv2
        DB::table('buildings')->where('id', 31)->update(['required_building_level' => 2]);
        // temple: NULL → CC Lv4
        DB::table('buildings')->where('id', 32)->update(['required_building_id' => 25, 'required_building_level' => 4]);
        // hangar: CC Lv2 → CC Lv3
        DB::table('buildings')->where('id', 44)->update(['required_building_level' => 3]);
        // hospital: CC Lv2 → CC Lv3
        DB::table('buildings')->where('id', 46)->update(['required_building_level' => 3]);
        // denkmal: NULL → CC Lv5
        DB::table('buildings')->where('id', 50)->update(['required_building_id' => 25, 'required_building_level' => 5]);

        // ── Step 5: Update knowledge researches — prereqs + grid coords ──────
        //
        // All knowledge researches (90–95): sciencelab (31) Lv1, col 6 → col 4
        // knowledge_defense (96): sciencelab (31) Lv2, col 6 → col 4

        DB::table('researches')
            ->whereIn('id', [90, 91, 92, 93, 94, 95])
            ->update([
                'required_building_id'    => 31,
                'required_building_level' => 1,
                'column'                  => 4,
            ]);

        DB::table('researches')
            ->where('id', 96)
            ->update([
                'required_building_id'    => 31,
                'required_building_level' => 2,
                'column'                  => 4,
            ]);

        // ── Step 6: Update active ship grid coordinates ───────────────────────
        //
        // sonde(85):    (6,6) → (1,5)
        // frachter(47): (7,4) → (2,5)
        // korvette(37): (8,5) → (3,5)

        DB::table('ships')->where('id', 85)->update(['row' => 1, 'column' => 5]);
        DB::table('ships')->where('id', 47)->update(['row' => 2, 'column' => 5]);
        DB::table('ships')->where('id', 37)->update(['row' => 3, 'column' => 5]);

        // Update ship prerequisites per GDD §11.2:
        // sonde + frachter: hangar Lv1; korvette: hangar Lv2
        DB::table('ships')->where('id', 85)->update(['required_building_id' => 44, 'required_building_level' => 1]);
        DB::table('ships')->where('id', 47)->update(['required_building_id' => 44, 'required_building_level' => 1]);
        DB::table('ships')->where('id', 37)->update(['required_building_id' => 44, 'required_building_level' => 2]);

        // ── Step 7: Update personell grid coordinates + prerequisites ─────────
        //
        // engineer(35):  (1,0) → (1,0)  CC Lv5→CC Lv1
        // scientist(36): (3,1) → (3,0)  sciencelab Lv1 (already correct)
        // pilot(89):     (7,3) → (7,0)  hangar Lv1 (already correct)
        // trader(92):    (7,2) → (9,0)  bar Lv1 (already correct)
        // stratege(93):  (9,0) → (11,0) CC Lv3 (update — GDD says korvette unit but
        //                               we store building prereqs here; keep CC Lv3
        //                               as approximation — stratege prereq note added)

        DB::table('personell')->where('id', 36)->update(['column' => 0]);
        DB::table('personell')->where('id', 89)->update(['column' => 0]);
        // Move stratege to (11,0) BEFORE moving trader to (9,0) to avoid conflict
        DB::table('personell')->where('id', 93)->update(['row' => 11]);
        DB::table('personell')->where('id', 92)->update(['row' => 9, 'column' => 0]);

        // Fix engineer prerequisite: CC Lv5 → CC Lv1
        DB::table('personell')->where('id', 35)->update(['required_building_level' => 1]);

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Remove is_active columns
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::table('researches', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::table('ships', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::table('personell', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        // Note: coordinate and prerequisite changes are not reversed in down() —
        // re-run from testdata.sqlite.sql if a full rollback is needed.
    }
};
