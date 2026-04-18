<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Introduces the 7 Kenntnisse (practical colonial knowledge fields) as research entities.
 *
 * IDs 90–96. No decay (decay_rate=0) — knowledge is permanent once acquired.
 * Research AP (Wissenschaftler) required to unlock each level.
 *
 * Design context: GDD §10 (Kenntnisse), §6 (Supply-Cap).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('researches')->insert([
            ['id' => 90, 'purpose' => 'knowledge', 'name' => 'knowledge_construction', 'required_building_id' => null, 'required_building_level' => null, 'row' => 1, 'column' => 6, 'ap_for_levelup' => 3, 'max_status_points' => 20, 'decay_rate' => 0, 'supply_cost' => 0],
            ['id' => 91, 'purpose' => 'knowledge', 'name' => 'knowledge_cartography',  'required_building_id' => null, 'required_building_level' => null, 'row' => 2, 'column' => 6, 'ap_for_levelup' => 3, 'max_status_points' => 20, 'decay_rate' => 0, 'supply_cost' => 0],
            ['id' => 92, 'purpose' => 'knowledge', 'name' => 'knowledge_geology',      'required_building_id' => null, 'required_building_level' => null, 'row' => 3, 'column' => 6, 'ap_for_levelup' => 3, 'max_status_points' => 20, 'decay_rate' => 0, 'supply_cost' => 0],
            ['id' => 93, 'purpose' => 'knowledge', 'name' => 'knowledge_agronomy',     'required_building_id' => null, 'required_building_level' => null, 'row' => 4, 'column' => 6, 'ap_for_levelup' => 3, 'max_status_points' => 20, 'decay_rate' => 0, 'supply_cost' => 0],
            ['id' => 94, 'purpose' => 'knowledge', 'name' => 'knowledge_health',       'required_building_id' => null, 'required_building_level' => null, 'row' => 5, 'column' => 6, 'ap_for_levelup' => 3, 'max_status_points' => 20, 'decay_rate' => 0, 'supply_cost' => 0],
            ['id' => 95, 'purpose' => 'knowledge', 'name' => 'knowledge_trade',        'required_building_id' => null, 'required_building_level' => null, 'row' => 6, 'column' => 6, 'ap_for_levelup' => 3, 'max_status_points' => 20, 'decay_rate' => 0, 'supply_cost' => 0],
            ['id' => 96, 'purpose' => 'knowledge', 'name' => 'knowledge_defense',      'required_building_id' => null, 'required_building_level' => null, 'row' => 7, 'column' => 6, 'ap_for_levelup' => 3, 'max_status_points' => 20, 'decay_rate' => 0, 'supply_cost' => 0],
        ]);
    }

    public function down(): void
    {
        DB::table('researches')->whereIn('id', [90, 91, 92, 93, 94, 95, 96])->delete();
    }
};
