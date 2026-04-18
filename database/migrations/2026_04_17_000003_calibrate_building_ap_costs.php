<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Calibrates ap_for_levelup for all buildings based on gameplay role.
 *
 * Three tiers:
 *   - 10  AP: CommandCenter (tutorial speed — starter building, onboarding UX)
 *   - 15  AP: Aesthetic/civic buildings with low strategic impact
 *   - 20  AP: Standard production and civil buildings (baseline)
 *   - 25  AP: Mid-tier strategic buildings (bank, university)
 *   - 30  AP: High-tech military/infrastructure (hangar, secretOps, militarySpaceyard)
 *
 * Design context: GDD §4 (Gebäude-Balancing), balancing confirmed by owner 2026-04-17.
 */
return new class extends Migration
{
    /**
     * @var array<int, int>  building_id => ap_for_levelup
     */
    private array $costs = [
        25 => 10,   // commandCenter         — tutorial speed, starter building
        27 => 20,   // harvester
        28 => 20,   // housingComplex
        30 => 20,   // depot
        31 => 20,   // sciencelab
        32 => 15,   // temple
        41 => 20,   // bioFacility
        42 => 20,   // waterextractor
        44 => 30,   // hangar (civilianSpaceyard) — high-tech
        45 => 15,   // parc
        46 => 20,   // hospital
        48 => 20,   // public_security
        50 => 15,   // denkmal
        51 => 25,   // university
        52 => 15,   // bar
        53 => 20,   // stadium
        54 => 20,   // casino
        55 => 20,   // prison
        56 => 15,   // museum
        64 => 20,   // wastedisposal
        65 => 20,   // recyclingStation
        66 => 30,   // secretOps             — high-tech
        68 => 30,   // militarySpaceyard     — high-tech
        70 => 25,   // bank
    ];

    public function up(): void
    {
        foreach ($this->costs as $buildingId => $apCost) {
            DB::table('buildings')
                ->where('id', $buildingId)
                ->update(['ap_for_levelup' => $apCost]);
        }
    }

    public function down(): void
    {
        // Restore all to old default of 10
        DB::table('buildings')
            ->whereIn('id', array_keys($this->costs))
            ->update(['ap_for_levelup' => 10]);
    }
};
