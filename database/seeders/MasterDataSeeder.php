<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds decay_rate, supply_cost, and max_status_points into the master data tables.
 *
 * Values are derived from:
 *   - Supply costs: docs/GDD.md §6
 *   - decay_rate = max_status_points / ticks_until_lost  (GDD §7, max_status_points = 20)
 *
 * CommandCenter and HousingComplex have supply_cost = 0 (they define the cap, not consume it).
 * ColonyShip is in the DB but not used in the game concept; supply_cost = 0.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBuildings();
        $this->seedShips();
        $this->seedResearches();
    }

    private function seedBuildings(): void
    {
        // [id => [max_status_points, decay_rate, supply_cost]]
        // decay_rate = 20 / ticks_until_lost
        $data = [
            25 => [20, 0.07, 0],   // commandCenter  — Supply-Quelle, ticks_until_lost ~285
            27 => [20, 0.17, 2],   // industrieMine  — ticks_until_lost 120
            28 => [20, 0.13, 0],   // housingComplex — Supply-Quelle, ticks_until_lost 150
            30 => [20, 0.13, 3],   // depot          — ticks_until_lost 150
            31 => [20, 0.17, 8],   // sciencelab     — ticks_until_lost 120
            32 => [20, 0.13, 5],   // temple         — ticks_until_lost 150
            41 => [20, 0.17, 2],   // bioFacility    — ticks_until_lost 120
            43 => [20, 0.13, 7],   // tradecenter    — ticks_until_lost 150
            44 => [20, 0.12, 12],  // hangar         — ticks_until_lost 166
            46 => [20, 0.20, 10],  // hospital       — ticks_until_lost 100
            50 => [20, 0.05, 2],   // denkmal        — ticks_until_lost 400
            52 => [20, 0.20, 4],   // bar            — ticks_until_lost 100
        ];

        foreach ($data as $id => [$msp, $decayRate, $supplyCost]) {
            DB::table('buildings')->where('id', $id)->update([
                'max_status_points' => $msp,
                'decay_rate'        => $decayRate,
                'supply_cost'       => $supplyCost,
            ]);
        }
    }

    private function seedShips(): void
    {
        // [id => [max_status_points, decay_rate, supply_cost]]
        $data = [
            85 => [20, 0.10, 0],   // sonde    — unmanned, ticks_until_lost ~200
            37 => [20, 0.15, 14],  // korvette — ticks_until_lost 133
            47 => [20, 0.05, 6],   // frachter — ticks_until_lost 400
        ];

        foreach ($data as $id => [$msp, $decayRate, $supplyCost]) {
            DB::table('ships')->where('id', $id)->update([
                'max_status_points' => $msp,
                'decay_rate'        => $decayRate,
                'supply_cost'       => $supplyCost,
            ]);
        }
    }

    private function seedResearches(): void
    {
        // Kenntnisse values are set by migration 2026_04_12_000001_replace_researches_with_kenntnisse.
        // Nothing to do here — game:sync-knowledge keeps them in sync with config/knowledge.php.
    }
}
