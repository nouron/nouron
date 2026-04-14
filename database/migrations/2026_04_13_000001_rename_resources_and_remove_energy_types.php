<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3a Resource Cleanup:
 *  - Ferum (ID 4)     → Werkstoffe  (abbreviation E → Co)
 *  - Silicates (ID 5) → Organika    (abbreviation S → Or)
 *  - Remove Wasser (ID 3) — logically subsumed by Versorgung (Supply)
 *  - Remove ENrg (ID 6), LNrg (ID 8), ANrg (ID 10) — energy types abolished in Phase 3
 *
 * Remaining resources: Credits (1), Versorgung (2), Werkstoffe (4), Organika (5), Moral (12).
 *
 * Associated colony_resources, fleet_resources, and trade_resources rows for
 * the removed IDs are also deleted to keep referential integrity.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Rename Ferum → Werkstoffe
        DB::table('resources')->where('id', 4)->update([
            'name'         => 'res_compounds',
            'abbreviation' => 'Co',
            'icon'         => 'resicon-compounds',
        ]);

        // Rename Silicates → Organika
        DB::table('resources')->where('id', 5)->update([
            'name'         => 'res_organics',
            'abbreviation' => 'Or',
            'icon'         => 'resicon-organics',
        ]);

        // Remove Wasser (subsumed by Versorgung) and energy types
        DB::table('colony_resources')->whereIn('resource_id', [3, 6, 8, 10])->delete();
        DB::table('fleet_resources')->whereIn('resource_id', [3, 6, 8, 10])->delete();
        DB::table('trade_resources')->whereIn('resource_id', [3, 6, 8, 10])->delete();
        DB::table('resources')->whereIn('id', [3, 6, 8, 10])->delete();
    }

    public function down(): void
    {
        // Restore Werkstoffe → Ferum
        DB::table('resources')->where('id', 4)->update([
            'name'         => 'res_ferum',
            'abbreviation' => 'E',
            'icon'         => 'resicon-iron',
        ]);

        // Restore Organika → Silicates
        DB::table('resources')->where('id', 5)->update([
            'name'         => 'res_silicates',
            'abbreviation' => 'S',
            'icon'         => 'resicon-silicates',
        ]);

        // Re-insert energy type resources (without colony/fleet/trade data)
        DB::table('resources')->insert([
            ['id' => 3,  'name' => 'res_water', 'abbreviation' => 'W',    'trigger' => 'Level',    'is_tradeable' => 1, 'start_amount' => 500, 'icon' => 'resicon-water'],
            ['id' => 6,  'name' => 'res_ena',   'abbreviation' => 'ENrg', 'trigger' => 'Constant', 'is_tradeable' => 1, 'start_amount' => 100, 'icon' => 'resicon-ena'],
            ['id' => 8,  'name' => 'res_lho',   'abbreviation' => 'LNrg', 'trigger' => 'Constant', 'is_tradeable' => 1, 'start_amount' => 100, 'icon' => 'resicon-lho'],
            ['id' => 10, 'name' => 'res_aku',   'abbreviation' => 'ANrg', 'trigger' => 'Constant', 'is_tradeable' => 1, 'start_amount' => 100, 'icon' => 'resicon-aku'],
        ]);
    }
};
