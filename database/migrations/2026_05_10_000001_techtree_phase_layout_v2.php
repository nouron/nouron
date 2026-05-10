<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Techtree — Phase layout v2 (game-design review 2026-05-10).
 *
 * Changes from v1 (000003_techtree_phase_based_layout):
 *   - bar       (B52):  Phase 1 R2C1  → Phase 2 R2C1  (needs bar for Trader-loop)
 *   - trader    (P92):  Phase 1 R3C3  → Phase 2 R3C3  (gate: bar Lv1)
 *   - infirmary (B46):  Phase 3 R1C1  → Phase 2 R1C3  (welfare, not military)
 *   - knowledge_health (R94): Phase 3 R3C1 → Phase 2 R6C1 (follows infirmary)
 *   - knowledge_geology (R92): Phase 2 R5C3 → Phase 3 R3C1 (deeper mining knowledge)
 *   - knowledge_construction (R90): Phase 2 R3C3 → R4C3 (shifted down for trader slot)
 *   - knowledge_agronomy (R93): Phase 2 R4C3 → R5C3 (shifted down)
 *
 * Final Phase 1 (CC Lv1): housingComplex, harvester, bioFacility, engineer
 * Final Phase 2 (CC Lv2): depot, sciencelab, infirmary, bar, scientist, trader,
 *                          knowledge_construction, knowledge_agronomy,
 *                          knowledge_health, knowledge_trade
 * Final Phase 3 (CC Lv3): hangar, strategist, drone, pilot, knowledge_geology,
 *                          freighter, knowledge_cartography, corvette, knowledge_defense
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // Step 1: move knowledge_health OUT of Phase 3 R3C1 first (frees that slot)
        DB::table('researches')->where('id', 94)->update(['phase' => 2, 'row' => 6, 'column' => 1]); // knowledge_health → P2 R6C1

        // Step 2: move knowledge_geology from Phase 2 R5C3 → Phase 3 R3C1 (now free)
        DB::table('researches')->where('id', 92)->update(['phase' => 3, 'row' => 3, 'column' => 1]); // knowledge_geology → P3 R3C1

        // Step 3: shift knowledge_agronomy R4C3 → R5C3 (geology freed R5C3)
        DB::table('researches')->where('id', 93)->update(['row' => 5, 'column' => 3]); // knowledge_agronomy → P2 R5C3

        // Step 4: shift knowledge_construction R3C3 → R4C3 (agronomy freed R4C3)
        DB::table('researches')->where('id', 90)->update(['row' => 4, 'column' => 3]); // knowledge_construction → P2 R4C3

        // Step 5: move trader Phase 1 R3C3 → Phase 2 R3C3 (construction freed R3C3)
        DB::table('personell')->where('id', 92)->update(['phase' => 2, 'row' => 3, 'column' => 3]); // trader → P2 R3C3

        // Step 6: move bar Phase 1 R2C1 → Phase 2 R2C1 (same position, different phase)
        DB::table('buildings')->where('id', 52)->update(['phase' => 2, 'row' => 2, 'column' => 1]); // bar → P2 R2C1

        // Step 7: move infirmary Phase 3 R1C1 → Phase 2 R1C3
        DB::table('buildings')->where('id', 46)->update(['phase' => 2, 'row' => 1, 'column' => 3]); // infirmary → P2 R1C3

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Restore v1 positions
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::table('researches')->where('id', 94)->update(['phase' => 3, 'row' => 3, 'column' => 1]); // knowledge_health
        DB::table('researches')->where('id', 92)->update(['phase' => 2, 'row' => 5, 'column' => 3]); // knowledge_geology
        DB::table('researches')->where('id', 93)->update(['row' => 4, 'column' => 3]);               // knowledge_agronomy
        DB::table('researches')->where('id', 90)->update(['row' => 3, 'column' => 3]);               // knowledge_construction
        DB::table('personell')->where('id', 92)->update(['phase' => 1, 'row' => 3, 'column' => 3]); // trader
        DB::table('buildings')->where('id', 52)->update(['phase' => 1, 'row' => 2, 'column' => 1]); // bar
        DB::table('buildings')->where('id', 46)->update(['phase' => 3, 'row' => 1, 'column' => 1]); // infirmary

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
