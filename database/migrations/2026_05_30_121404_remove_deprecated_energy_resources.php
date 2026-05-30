<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // ENrg (6), LNrg (8), ANrg (10) deprecated with race system (GDD §3).
    // Filtered via whitelist in resourcebar since Phase 3a; now removed from master table.
    private const DEPRECATED_IDS = [6, 8, 10];

    public function up(): void
    {
        DB::table('colony_resources')->whereIn('resource_id', self::DEPRECATED_IDS)->delete();
        DB::table('fleet_resources')->whereIn('resource_id', self::DEPRECATED_IDS)->delete();
        DB::table('trade_resources')->whereIn('resource_id', self::DEPRECATED_IDS)->delete();
        DB::table('research_costs')->whereIn('resource_id', self::DEPRECATED_IDS)->delete();
        DB::table('resources')->whereIn('id', self::DEPRECATED_IDS)->delete();
    }

    public function down(): void
    {
        DB::table('resources')->insert([
            ['id' => 6,  'abbreviation' => 'ENrg', 'name' => 'res_ena', 'trigger' => 'Constant', 'is_tradeable' => 1, 'start_amount' => 100, 'icon' => 'resicon-ena'],
            ['id' => 8,  'abbreviation' => 'LNrg', 'name' => 'res_lho', 'trigger' => 'Constant', 'is_tradeable' => 1, 'start_amount' => 100, 'icon' => 'resicon-lho'],
            ['id' => 10, 'abbreviation' => 'ANrg', 'name' => 'res_aku', 'trigger' => 'Constant', 'is_tradeable' => 1, 'start_amount' => 100, 'icon' => 'resicon-aku'],
        ]);
    }
};
