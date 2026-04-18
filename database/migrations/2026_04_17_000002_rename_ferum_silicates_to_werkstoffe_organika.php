<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renames resource IDs 4 and 5 to match the new GDD §3 resource model.
 *
 * ID 4: res_ferum  (E)  → res_werkstoffe (Co)  — produced by Harvester (building 27)
 * ID 5: res_silicates (S) → res_organika (Or) — produced by bioFacility (building 41)
 *
 * Both start_amount values are set to 0: players no longer receive these resources
 * at game start — they must be produced via buildings.
 *
 * Design context: GDD §3 (Ressourcen), §4 (Harvester), §5 (bioFacility).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::table('resources')->where('id', 4)->update([
            'name'         => 'res_werkstoffe',
            'abbreviation' => 'Co',
            'start_amount' => 0,
        ]);

        DB::table('resources')->where('id', 5)->update([
            'name'         => 'res_organika',
            'abbreviation' => 'Or',
            'start_amount' => 0,
        ]);

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::table('resources')->where('id', 4)->update([
            'name'         => 'res_ferum',
            'abbreviation' => 'E',
            'start_amount' => 500,
        ]);

        DB::table('resources')->where('id', 5)->update([
            'name'         => 'res_silicates',
            'abbreviation' => 'S',
            'start_amount' => 500,
        ]);
    }
};
