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
            25 => [20, 0.07, 0],   // commandCenter     — Supply-Quelle, ticks_until_lost ~285
            27 => [20, 0.17, 2],   // oremine           — ticks_until_lost 120
            28 => [20, 0.13, 0],   // housingComplex    — Supply-Quelle, ticks_until_lost 150
            30 => [20, 0.13, 3],   // depot             — ticks_until_lost 150
            31 => [20, 0.17, 8],   // sciencelab        — ticks_until_lost 120
            32 => [20, 0.13, 5],   // temple            — ticks_until_lost 150
            41 => [20, 0.17, 2],   // silicatemine      — ticks_until_lost 120
            42 => [20, 0.17, 2],   // waterextractor    — ticks_until_lost 120

            44 => [20, 0.12, 20],  // civilianSpaceyard — ticks_until_lost 166
            45 => [20, 0.13, 4],   // parc              — ticks_until_lost 150
            46 => [20, 0.20, 10],  // hospital          — ticks_until_lost 100
            48 => [20, 0.17, 8],   // public_security   — ticks_until_lost 120
            50 => [20, 0.05, 2],   // denkmal           — ticks_until_lost 400
            51 => [20, 0.17, 8],   // university        — ticks_until_lost 120
            52 => [20, 0.20, 4],   // bar               — ticks_until_lost 100
            53 => [20, 0.15, 14],  // stadium           — ticks_until_lost 133
            54 => [20, 0.20, 9],   // casino            — ticks_until_lost 100
            55 => [20, 0.10, 15],  // prison            — ticks_until_lost 200
            56 => [20, 0.10, 5],   // museum            — ticks_until_lost 200
            64 => [20, 0.13, 6],   // wastedisposal     — ticks_until_lost 150
            65 => [20, 0.13, 6],   // recyclingStation  — ticks_until_lost 150
            66 => [20, 0.13, 26],  // secretOps         — ticks_until_lost 150
            68 => [20, 0.08, 30],  // militarySpaceyard — ticks_until_lost 250
            70 => [20, 0.10, 14],  // bank              — ticks_until_lost 200
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
            29 => [20, 0.16, 14],  // frigate1          — ticks_until_lost 125
            37 => [20, 0.15, 8],   // fighter1          — ticks_until_lost 133
            47 => [20, 0.05, 2],   // smallTransporter  — ticks_until_lost 400
            49 => [20, 0.10, 25],  // battlecruiser1    — ticks_until_lost 200
            83 => [20, 0.07, 4],   // mediumTransporter — ticks_until_lost ~285
            84 => [20, 0.06, 7],   // largeTransporter  — ticks_until_lost 333
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
        // All researches: max_status_points = 20, ticks_until_lost = 160 → decay_rate = 0.13
        // Military: higher supply cost (8 vs 5)
        $standard = [33, 34, 39, 72, 73, 74, 76, 79, 80];
        foreach ($standard as $id) {
            DB::table('researches')->where('id', $id)->update([
                'max_status_points' => 20,
                'decay_rate'        => 0.13,
                'supply_cost'       => 5,
            ]);
        }

        DB::table('researches')->where('id', 81)->update([
            'max_status_points' => 20,
            'decay_rate'        => 0.13,
            'supply_cost'       => 8,
        ]);
    }
}
