<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ships: German name column values → English keys
        DB::table('ships')->where('name', 'ship_sonde')->update(['name' => 'ship_probe']);
        DB::table('ships')->where('name', 'ship_korvette')->update(['name' => 'ship_corvette']);
        DB::table('ships')->where('name', 'ship_frachter')->update(['name' => 'ship_freighter']);

        // Buildings: denkmal → monument
        DB::table('buildings')->where('name', 'building_denkmal')->update(['name' => 'building_monument']);

        // Personell: stratege → strategist
        DB::table('personell')->where('name', 'techs_stratege')->update(['name' => 'techs_strategist']);
    }

    public function down(): void
    {
        DB::table('ships')->where('name', 'ship_probe')->update(['name' => 'ship_sonde']);
        DB::table('ships')->where('name', 'ship_corvette')->update(['name' => 'ship_korvette']);
        DB::table('ships')->where('name', 'ship_freighter')->update(['name' => 'ship_frachter']);
        DB::table('buildings')->where('name', 'building_monument')->update(['name' => 'building_denkmal']);
        DB::table('personell')->where('name', 'techs_strategist')->update(['name' => 'techs_stratege']);
    }
};
// Note: ship_probe → ship_drone handled separately via migration below
