<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Techtree — Phase-based layout (CC level = milestone).
 *
 * Adds a `phase` column (1-5) to all four master tables. Items in the same
 * phase share a 3-column grid section on the techtree page. CommandCenter
 * stays at phase=0 and is excluded from the grid (shown as section header).
 *
 * The personell table has an inline UNIQUE (row, column) table constraint that
 * cannot be dropped with DROP INDEX. We recreate the table without it and with
 * the phase column already present. The method is idempotent: it detects whether
 * the inline constraint is still present before recreating.
 *
 * For buildings/researches/ships the old named indexes are dropped (IF EXISTS)
 * and the phase column is added only when not already present.
 *
 *  Phase 1 (CC Lv1): housingComplex, harvester, bar, bioFacility, engineer, trader
 *  Phase 2 (CC Lv2): depot, sciencelab, scientist, construction, agronomy, geology, trade
 *  Phase 3 (CC Lv3): infirmary, hangar, strategist, pilot, drone, health, cartography,
 *                     freighter, defense, corvette
 *  Phase 4 (CC Lv4): temple
 *  Phase 5 (CC Lv5): monument
 *
 *  Grid coordinates (col 1-3, row 1-N, 1-indexed):
 *  Phase 1:  R1 C1=housingComplex  C2=harvester
 *            R2 C1=bar             C2=bioFacility   C3=engineer
 *            R3                                      C3=trader
 *  Phase 2:  R1 C1=depot           C2=sciencelab
 *            R2                                      C3=scientist
 *            R3–R6                                   C3=construction/agronomy/geology/trade
 *  Phase 3:  R1 C1=infirmary       C2=hangar         C3=strategist
 *            R2                    C2=drone           C3=pilot
 *            R3 C1=health          C2=freighter       C3=cartography
 *            R4                    C2=corvette         C3=defense
 *  Phase 4:  R1 C2=temple
 *  Phase 5:  R1 C2=monument
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // ── Drop old named (row, column) unique indexes ──────────────────────
        DB::statement('DROP INDEX IF EXISTS "row_column"');
        DB::statement('DROP INDEX IF EXISTS "researches_row_column"');
        DB::statement('DROP INDEX IF EXISTS "ships_row_column"');

        // ── Add phase column to buildings / researches / ships ───────────────
        // Use PRAGMA check for idempotency (ALTER TABLE … ADD COLUMN has no IF NOT EXISTS)
        foreach (['buildings', 'researches', 'ships'] as $table) {
            $cols = array_column(DB::select("PRAGMA table_info({$table})"), 'name');
            if (!in_array('phase', $cols)) {
                DB::statement("ALTER TABLE \"{$table}\" ADD COLUMN phase INTEGER NOT NULL DEFAULT 0");
            }
        }

        // ── Recreate personell without its inline UNIQUE (row, column) ───────
        // The personell table was created with an inline CONSTRAINT that cannot
        // be dropped via DROP INDEX — it must be removed by rebuilding the table.
        // This block is idempotent: it checks for the constraint before acting.
        $personellDdl = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='personell'")[0]->sql ?? '';
        if (str_contains($personellDdl, 'CONSTRAINT personell_row_column')) {
            DB::statement('CREATE TABLE "personell_backup" AS SELECT * FROM "personell"');
            DB::statement('DROP TABLE "personell"');
            DB::statement('
                CREATE TABLE "personell" (
                    "id"                      INTEGER NOT NULL,
                    "purpose"                 TEXT    NOT NULL,
                    "name"                    TEXT    NOT NULL UNIQUE,
                    "required_building_id"    INTEGER DEFAULT NULL REFERENCES "buildings"("id"),
                    "required_building_level" INTEGER DEFAULT NULL,
                    "row"                     INTEGER NOT NULL,
                    "column"                  INTEGER NOT NULL,
                    "max_status_points"       INTEGER DEFAULT NULL,
                    "is_active"               INTEGER NOT NULL DEFAULT 1,
                    "phase"                   INTEGER NOT NULL DEFAULT 0,
                    PRIMARY KEY ("id")
                )
            ');
            // Copy from backup — adapt to whether backup already has phase/is_active columns
            $backupCols = array_column(DB::select('PRAGMA table_info("personell_backup")'), 'name');
            if (in_array('phase', $backupCols) && in_array('is_active', $backupCols)) {
                DB::statement('INSERT INTO "personell" SELECT id,purpose,name,required_building_id,required_building_level,"row","column",max_status_points,is_active,phase FROM "personell_backup"');
            } elseif (in_array('is_active', $backupCols)) {
                DB::statement('INSERT INTO "personell" (id,purpose,name,required_building_id,required_building_level,"row","column",max_status_points,is_active) SELECT id,purpose,name,required_building_id,required_building_level,"row","column",max_status_points,is_active FROM "personell_backup"');
            } else {
                DB::statement('INSERT INTO "personell" (id,purpose,name,required_building_id,required_building_level,"row","column",max_status_points) SELECT id,purpose,name,required_building_id,required_building_level,"row","column",max_status_points FROM "personell_backup"');
            }
            DB::statement('DROP TABLE "personell_backup"');
        } else {
            // Table already rebuilt — just add phase column if missing
            $cols = array_column(DB::select('PRAGMA table_info("personell")'), 'name');
            if (!in_array('phase', $cols)) {
                DB::statement('ALTER TABLE "personell" ADD COLUMN "phase" INTEGER NOT NULL DEFAULT 0');
            }
        }

        // ── Phase 1 (CC Lv1) ──────────────────────────────────────────────────
        DB::table('buildings')->where('id', 28)->update(['phase' => 1, 'row' => 1, 'column' => 1]); // housingComplex
        DB::table('buildings')->where('id', 27)->update(['phase' => 1, 'row' => 1, 'column' => 2]); // harvester
        DB::table('buildings')->where('id', 52)->update(['phase' => 1, 'row' => 2, 'column' => 1]); // bar
        DB::table('buildings')->where('id', 41)->update(['phase' => 1, 'row' => 2, 'column' => 2]); // bioFacility
        DB::table('personell')->where('id', 35)->update(['phase' => 1, 'row' => 2, 'column' => 3]); // engineer
        DB::table('personell')->where('id', 92)->update(['phase' => 1, 'row' => 3, 'column' => 3]); // trader

        // ── Phase 2 (CC Lv2) ──────────────────────────────────────────────────
        DB::table('buildings')->where('id', 30)->update(['phase' => 2, 'row' => 1, 'column' => 1]); // depot
        DB::table('buildings')->where('id', 31)->update(['phase' => 2, 'row' => 1, 'column' => 2]); // sciencelab
        DB::table('personell')->where('id', 36)->update(['phase' => 2, 'row' => 2, 'column' => 3]); // scientist
        DB::table('researches')->where('id', 90)->update(['phase' => 2, 'row' => 3, 'column' => 3]); // knowledge_construction
        DB::table('researches')->where('id', 93)->update(['phase' => 2, 'row' => 4, 'column' => 3]); // knowledge_agronomy
        DB::table('researches')->where('id', 92)->update(['phase' => 2, 'row' => 5, 'column' => 3]); // knowledge_geology
        DB::table('researches')->where('id', 95)->update(['phase' => 2, 'row' => 6, 'column' => 3]); // knowledge_trade

        // ── Phase 3 (CC Lv3) ──────────────────────────────────────────────────
        DB::table('buildings')->where('id', 46)->update(['phase' => 3, 'row' => 1, 'column' => 1]); // infirmary
        DB::table('buildings')->where('id', 44)->update(['phase' => 3, 'row' => 1, 'column' => 2]); // hangar
        DB::table('personell')->where('id', 93)->update(['phase' => 3, 'row' => 1, 'column' => 3]); // strategist
        DB::table('ships')->where('id', 85)->update(['phase' => 3, 'row' => 2, 'column' => 2]);     // drone
        DB::table('personell')->where('id', 89)->update(['phase' => 3, 'row' => 2, 'column' => 3]); // pilot
        DB::table('researches')->where('id', 94)->update(['phase' => 3, 'row' => 3, 'column' => 1]); // knowledge_health
        DB::table('ships')->where('id', 47)->update(['phase' => 3, 'row' => 3, 'column' => 2]);     // freighter
        DB::table('researches')->where('id', 91)->update(['phase' => 3, 'row' => 3, 'column' => 3]); // knowledge_cartography
        DB::table('ships')->where('id', 37)->update(['phase' => 3, 'row' => 4, 'column' => 2]);     // corvette
        DB::table('researches')->where('id', 96)->update(['phase' => 3, 'row' => 4, 'column' => 3]); // knowledge_defense

        // ── Phase 4 (CC Lv4) ──────────────────────────────────────────────────
        DB::table('buildings')->where('id', 32)->update(['phase' => 4, 'row' => 1, 'column' => 2]); // temple

        // ── Phase 5 (CC Lv5) ──────────────────────────────────────────────────
        DB::table('buildings')->where('id', 50)->update(['phase' => 5, 'row' => 1, 'column' => 2]); // monument

        // CommandCenter (ID 25) stays phase=0 (shown as section header, not grid item)

        // ── New unique indexes: (phase, row, column), partial (exclude phase=0) ──
        DB::statement('DROP INDEX IF EXISTS "buildings_phase_row_col"');
        DB::statement('DROP INDEX IF EXISTS "researches_phase_row_col"');
        DB::statement('DROP INDEX IF EXISTS "ships_phase_row_col"');
        DB::statement('DROP INDEX IF EXISTS "personell_phase_row_col"');

        DB::statement('CREATE UNIQUE INDEX "buildings_phase_row_col"  ON "buildings"  ("phase","row","column") WHERE "phase" > 0');
        DB::statement('CREATE UNIQUE INDEX "researches_phase_row_col" ON "researches" ("phase","row","column") WHERE "phase" > 0');
        DB::statement('CREATE UNIQUE INDEX "ships_phase_row_col"      ON "ships"      ("phase","row","column") WHERE "phase" > 0');
        DB::statement('CREATE UNIQUE INDEX "personell_phase_row_col"  ON "personell"  ("phase","row","column") WHERE "phase" > 0');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Phase column removal not implemented — restore from backup if needed
    }
};
