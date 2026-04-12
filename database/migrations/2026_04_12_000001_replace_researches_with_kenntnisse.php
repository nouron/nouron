<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replace the 10 old "research" records with 7 new Kenntnisse (IDs 90–96).
 *
 * Old IDs removed: 33, 34, 39, 72, 73, 74, 76, 79, 80, 81
 * New IDs inserted: 90 construction, 91 cartography, 92 geology,
 *                   93 agronomy, 94 health, 95 trade, 96 defense
 *
 * Associated rows in colony_researches, fleet_researches, research_costs,
 * and trade_researches are also cleaned up.
 */
return new class extends Migration
{
    private const OLD_IDS = [33, 34, 39, 72, 73, 74, 76, 79, 80, 81];

    /** @var array<array{id:int,name:string,purpose:string,row:int,column:int,decay_rate:float,max_status_points:int,ap_for_levelup:int}> */
    private const NEW_KENNTNISSE = [
        ['id' => 90, 'name' => 'knowledge_construction', 'purpose' => 'industry',     'row' => 1, 'column' => 1],
        ['id' => 91, 'name' => 'knowledge_cartography',  'purpose' => 'exploration',  'row' => 1, 'column' => 2],
        ['id' => 92, 'name' => 'knowledge_geology',      'purpose' => 'industry',     'row' => 1, 'column' => 3],
        ['id' => 93, 'name' => 'knowledge_agronomy',     'purpose' => 'civil',        'row' => 1, 'column' => 4],
        ['id' => 94, 'name' => 'knowledge_health',       'purpose' => 'civil',        'row' => 2, 'column' => 2],
        ['id' => 95, 'name' => 'knowledge_trade',        'purpose' => 'economy',      'row' => 2, 'column' => 3],
        ['id' => 96, 'name' => 'knowledge_defense',      'purpose' => 'military',     'row' => 2, 'column' => 4],
    ];

    public function up(): void
    {
        // 1. Remove dependent data for old researches
        DB::table('colony_researches')->whereIn('research_id', self::OLD_IDS)->delete();
        DB::table('fleet_researches')->whereIn('research_id', self::OLD_IDS)->delete();
        DB::table('research_costs')->whereIn('research_id', self::OLD_IDS)->delete();
        DB::table('trade_researches')->whereIn('research_id', self::OLD_IDS)->delete();

        // 2. Remove old research master records
        DB::table('researches')->whereIn('id', self::OLD_IDS)->delete();

        // 3. Insert 7 new Kenntnisse
        foreach (self::NEW_KENNTNISSE as $k) {
            DB::table('researches')->insert([
                'id'                  => $k['id'],
                'name'                => $k['name'],
                'purpose'             => $k['purpose'],
                'row'                 => $k['row'],
                'column'              => $k['column'],
                'ap_for_levelup'      => 1,
                'decay_rate'          => 0.13,
                'max_status_points'   => 20,
                'supply_cost'         => 0,
                'required_building_id'    => null,
                'required_building_level' => null,
            ]);
        }
    }

    public function down(): void
    {
        // Remove Kenntnisse and their dependent data
        $newIds = array_column(self::NEW_KENNTNISSE, 'id');

        DB::table('colony_researches')->whereIn('research_id', $newIds)->delete();
        DB::table('fleet_researches')->whereIn('research_id', $newIds)->delete();
        DB::table('research_costs')->whereIn('research_id', $newIds)->delete();
        DB::table('trade_researches')->whereIn('research_id', $newIds)->delete();
        DB::table('researches')->whereIn('id', $newIds)->delete();

        // Re-insert old research master records (no costs/colony data — dev env only)
        $oldRecords = [
            ['id' => 33, 'name' => 'techs_biology',          'purpose' => 'civil',     'row' => 1, 'column' => 1],
            ['id' => 34, 'name' => 'techs_languages',         'purpose' => 'politics',  'row' => 1, 'column' => 2],
            ['id' => 39, 'name' => 'techs_mathematics',       'purpose' => 'civil',     'row' => 1, 'column' => 3],
            ['id' => 72, 'name' => 'techs_medicalScience',    'purpose' => 'civil',     'row' => 1, 'column' => 4],
            ['id' => 73, 'name' => 'techs_physics',           'purpose' => 'civil',     'row' => 1, 'column' => 5],
            ['id' => 74, 'name' => 'techs_chemistry',         'purpose' => 'civil',     'row' => 1, 'column' => 6],
            ['id' => 76, 'name' => 'techs_economicScience',   'purpose' => 'economy',   'row' => 2, 'column' => 1],
            ['id' => 79, 'name' => 'techs_diplomacy',         'purpose' => 'politics',  'row' => 2, 'column' => 2],
            ['id' => 80, 'name' => 'techs_politicalScience',  'purpose' => 'politics',  'row' => 2, 'column' => 3],
            ['id' => 81, 'name' => 'techs_military',          'purpose' => 'military',  'row' => 2, 'column' => 4],
        ];

        foreach ($oldRecords as $r) {
            DB::table('researches')->insert([
                'id'                      => $r['id'],
                'name'                    => $r['name'],
                'purpose'                 => $r['purpose'],
                'row'                     => $r['row'],
                'column'                  => $r['column'],
                'ap_for_levelup'          => 1,
                'decay_rate'              => null,
                'max_status_points'       => null,
                'supply_cost'             => null,
                'required_building_id'    => null,
                'required_building_level' => null,
            ]);
        }
    }
};
