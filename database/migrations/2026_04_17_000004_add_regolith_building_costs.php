<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds Regolith (resource_id=3) as a construction cost for all buildings
 * except CommandCenter and Harvester.
 *
 * CC (25) and Harvester (27) are intentionally excluded — they are the
 * game entry points: CC starts at level 1 and Harvester is the only source
 * of Regolith in the early game, so they must remain accessible with Credits
 * alone to avoid a bootstrap deadlock.
 *
 * Cost tiers are proportional to building complexity and strategic value
 * (40–300 Rg), confirmed by owner/game-designer 2026-04-17.
 *
 * Design context: GDD §3 (Ressourcen), §4 (Gebäude-Balancing)
 */
return new class extends Migration
{
    /**
     * @var array<int, int>  building_id => regolith_amount
     */
    private array $regolithCosts = [
        28 =>  50,   // housingComplex
        30 =>  80,   // depot
        31 => 100,   // sciencelab
        32 =>  60,   // temple
        41 =>  60,   // bioFacility
        44 => 200,   // hangar (civilianSpaceyard)
        45 =>  50,   // parc
        46 =>  80,   // hospital
        48 =>  60,   // public_security
        50 => 100,   // denkmal
        51 => 120,   // university
        52 =>  40,   // bar
        53 => 150,   // stadium
        54 => 120,   // casino
        56 =>  80,   // museum
        64 =>  60,   // wastedisposal
        65 =>  80,   // recyclingStation
        66 => 200,   // secretOps
        68 => 300,   // militarySpaceyard
        70 => 150,   // bank
    ];

    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        $rows = [];
        foreach ($this->regolithCosts as $buildingId => $amount) {
            $rows[] = [
                'building_id' => $buildingId,
                'resource_id' => 3,
                'amount'      => $amount,
            ];
        }
        DB::table('building_costs')->insert($rows);

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // Remove all Regolith cost rows — does not affect Credits/Supply cost rows.
        DB::table('building_costs')->where('resource_id', 3)->delete();
    }
};
