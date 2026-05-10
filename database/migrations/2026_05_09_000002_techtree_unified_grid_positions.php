<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Techtree — unified 6-column grid positions.
 *
 * All 26 active entities share one coordinate space so children appear
 * directly below their parents and dependency arrows stay short.
 *
 * Column allocation:
 *   Col 0        — Personell (advisors)
 *   Cols 1–3     — Buildings (dependency tree)
 *   Col 4        — Knowledge (researches)
 *   Col 5        — Ships
 *
 * Rows are determined by dependency depth (child row > parent row).
 *
 *        Col0       Col1         Col2         Col3         Col4          Col5
 *  R0               ——          CC           ——
 *  R1               housing      harvester    ——
 *  R2  engineer     bioFac       sciencelab   bar          construction
 *  R3  scientist    depot        infirmary    hangar       agronomy
 *  R4  trader       ——           temple       ——           health        drone
 *  R5  pilot        ——           ——           ——           geology       freighter
 *  R6  strategist   ——           monument     ——           trade         corvette
 *  R7               ——           ——           ——           cartography
 *  R8               ——           ——           ——           defense
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // Bump all rows by 100 to clear UNIQUE (row, col) conflicts
        foreach (['buildings', 'researches', 'ships', 'personell'] as $table) {
            DB::table($table)->update(['row' => DB::raw('"row" + 100')]);
        }

        // ── Buildings (cols 1–3) ───────────────────────────────────────────────
        foreach ([
            25 => [0, 2],   // commandCenter
            28 => [1, 1],   // housingComplex
            27 => [1, 2],   // harvester
            41 => [2, 1],   // bioFacility
            31 => [2, 2],   // sciencelab
            52 => [2, 3],   // bar
            30 => [3, 1],   // depot
            46 => [3, 2],   // infirmary
            44 => [3, 3],   // hangar
            32 => [4, 2],   // temple
            50 => [6, 2],   // monument
        ] as $id => [$row, $col]) {
            DB::table('buildings')->where('id', $id)->update(['row' => $row, 'column' => $col]);
        }

        // ── Researches / Knowledge (col 4, plus col 5 for construction) ────────
        foreach ([
            90 => [3, 5],   // knowledge_construction   (sciencelab Lv1)
            93 => [3, 4],   // knowledge_agronomy        (bioFacility Lv1)
            94 => [4, 4],   // knowledge_health          (infirmary  Lv1)
            92 => [5, 4],   // knowledge_geology         (harvester  Lv1)
            95 => [6, 4],   // knowledge_trade           (bar        Lv1)
            91 => [7, 4],   // knowledge_cartography     (hangar     Lv1)
            96 => [8, 4],   // knowledge_defense         (hangar     Lv2)
        ] as $id => [$row, $col]) {
            DB::table('researches')->where('id', $id)->update(['row' => $row, 'column' => $col]);
        }

        // ── Ships (col 5) ─────────────────────────────────────────────────────
        foreach ([
            85 => [4, 5],   // ship_drone      (hangar Lv1)
            47 => [5, 5],   // ship_freighter  (hangar Lv2)
            37 => [6, 5],   // ship_corvette   (hangar Lv3)
        ] as $id => [$row, $col]) {
            DB::table('ships')->where('id', $id)->update(['row' => $row, 'column' => $col]);
        }

        // ── Personell (col 0) ─────────────────────────────────────────────────
        foreach ([
            35 => [2, 0],   // engineer    (housingComplex Lv1)
            36 => [3, 0],   // scientist   (sciencelab     Lv1)
            92 => [4, 0],   // trader      (bar            Lv1)
            89 => [5, 0],   // pilot       (hangar         Lv1)
            93 => [6, 0],   // strategist  (commandCenter  Lv3)
        ] as $id => [$row, $col]) {
            DB::table('personell')->where('id', $id)->update(['row' => $row, 'column' => $col]);
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Grid position rollback not implemented — restore from backup if needed
    }
};
