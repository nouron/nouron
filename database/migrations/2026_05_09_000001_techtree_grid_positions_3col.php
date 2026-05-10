<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reassign techtree grid positions to a compact 3-column (col 0–2) layout.
 *
 * Strategy: bump all rows by 100 first to clear UNIQUE (row,col) conflicts,
 * then assign each active entity its final position.
 *
 * New building layout (dependency tree):
 *         Col0          Col1          Col2
 *  Row 0               CC
 *  Row 1  housing      harvester     sciencelab
 *  Row 2  bar          bioFacility   depot
 *  Row 3  infirmary    hangar        temple
 *  Row 4               monument
 *
 * Researches / Ships / Personell are each given compact 3-col grids
 * within their own sections.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // ── Buildings ──────────────────────────────────────────────────────────
        DB::table('buildings')->update(['row' => DB::raw('"row" + 100')]);
        foreach ([
            25 => [0, 1],   // commandCenter  — center of row 0
            28 => [1, 0],   // housingComplex
            27 => [1, 1],   // harvester
            31 => [1, 2],   // sciencelab
            52 => [2, 0],   // bar
            41 => [2, 1],   // bioFacility
            30 => [2, 2],   // depot
            46 => [3, 0],   // infirmary
            44 => [3, 1],   // hangar
            32 => [3, 2],   // temple
            50 => [4, 1],   // monument
        ] as $id => [$row, $col]) {
            DB::table('buildings')->where('id', $id)->update(['row' => $row, 'column' => $col]);
        }

        // ── Researches ─────────────────────────────────────────────────────────
        DB::table('researches')->update(['row' => DB::raw('"row" + 100')]);
        foreach ([
            90 => [0, 0],   // knowledge_construction
            91 => [0, 1],   // knowledge_cartography
            92 => [0, 2],   // knowledge_geology
            93 => [1, 0],   // knowledge_agronomy
            94 => [1, 1],   // knowledge_health
            95 => [1, 2],   // knowledge_trade
            96 => [2, 1],   // knowledge_defense
        ] as $id => [$row, $col]) {
            DB::table('researches')->where('id', $id)->update(['row' => $row, 'column' => $col]);
        }

        // ── Ships ──────────────────────────────────────────────────────────────
        DB::table('ships')->update(['row' => DB::raw('"row" + 100')]);
        foreach ([
            85 => [0, 0],   // ship_drone
            47 => [0, 1],   // ship_freighter
            37 => [0, 2],   // ship_corvette
        ] as $id => [$row, $col]) {
            DB::table('ships')->where('id', $id)->update(['row' => $row, 'column' => $col]);
        }

        // ── Personell ──────────────────────────────────────────────────────────
        DB::table('personell')->update(['row' => DB::raw('"row" + 100')]);
        foreach ([
            35 => [0, 0],   // engineer
            36 => [0, 1],   // scientist
            89 => [0, 2],   // pilot
            92 => [1, 0],   // trader
            93 => [1, 1],   // strategist
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
