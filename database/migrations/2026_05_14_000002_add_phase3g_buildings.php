<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3g — drei neue Gebäude: Sicherheits-Hub (53), Uplink-Station (54),
 * Handelsposten (55).
 *
 * Positionen im Techtree:
 *   Phase 2, Row 4: securityHub (col 1), uplinkStation (col 2)  [neben infirmary col 3]
 *   Phase 4, Row 1: tradingPost (col 3)                          [neben temple col 2]
 *
 * Baukosten (provisorisch, werden nach erstem Playtest kalibriert):
 *   securityHub:   100 Cr + 200 Rg + 200 Co
 *   uplinkStation: 100 Cr + 300 Rg          [keine Compounds — Zirkel-Risiko]
 *   tradingPost:   400 Cr + 200 Rg
 *
 * Operative Kosten: Supply lt. buildings.supply_cost (8 / 6 / 6).
 * Effekte werden in Services implementiert (nicht im Schema).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // ── Buildings ────────────────────────────────────────────────────────

        DB::table('buildings')->insert([
            [
                'id'                      => 53,
                'purpose'                 => 'civil',
                'name'                    => 'building_securityHub',
                'required_building_id'    => 25,   // commandCenter
                'required_building_level' => 2,
                'prime_colony_only'       => 0,
                'row'                     => 4,
                'column'                  => 1,
                'max_level'               => 3,
                'ap_for_levelup'          => 10,
                'max_status_points'       => 20,
                'decay_rate'              => 0.67,
                'supply_cost'             => 8,
                'is_instanced'            => 0,
                'is_active'               => 1,
                'phase'                   => 2,
            ],
            [
                'id'                      => 54,
                'purpose'                 => 'civil',
                'name'                    => 'building_uplinkStation',
                'required_building_id'    => 25,   // commandCenter
                'required_building_level' => 2,
                'prime_colony_only'       => 0,
                'row'                     => 4,
                'column'                  => 2,
                'max_level'               => 3,
                'ap_for_levelup'          => 10,
                'max_status_points'       => 20,
                'decay_rate'              => 0.67,
                'supply_cost'             => 6,
                'is_instanced'            => 0,
                'is_active'               => 1,
                'phase'                   => 2,
            ],
            [
                'id'                      => 55,
                'purpose'                 => 'civil',
                'name'                    => 'building_tradingPost',
                'required_building_id'    => 25,   // commandCenter
                'required_building_level' => 4,
                'prime_colony_only'       => 0,
                'row'                     => 1,
                'column'                  => 3,
                'max_level'               => 3,
                'ap_for_levelup'          => 10,
                'max_status_points'       => 20,
                'decay_rate'              => 0.67,
                'supply_cost'             => 6,
                'is_instanced'            => 0,
                'is_active'               => 1,
                'phase'                   => 4,
            ],
        ]);

        // ── Build costs ──────────────────────────────────────────────────────
        // resource_id: 1=credits, 3=regolith, 4=compounds

        DB::table('building_costs')->insert([
            // securityHub
            ['building_id' => 53, 'resource_id' => 1, 'amount' => 100],
            ['building_id' => 53, 'resource_id' => 3, 'amount' => 200],
            ['building_id' => 53, 'resource_id' => 4, 'amount' => 200],
            // uplinkStation — no compounds to avoid circular dependency
            ['building_id' => 54, 'resource_id' => 1, 'amount' => 100],
            ['building_id' => 54, 'resource_id' => 3, 'amount' => 300],
            // tradingPost
            ['building_id' => 55, 'resource_id' => 1, 'amount' => 400],
            ['building_id' => 55, 'resource_id' => 3, 'amount' => 200],
        ]);

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::table('building_costs')->whereIn('building_id', [53, 54, 55])->delete();
        DB::table('buildings')->whereIn('id', [53, 54, 55])->delete();

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
