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
 * Only legacy rows WITHOUT a config pendant are seeded here. Buildings, the
 * playable ships (config/ships.php) and knowledge (config/knowledge.php) are
 * synced from config by `game:sync-config`, which TestSeeder runs last (T20).
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedShips();
        $this->seedResearches();
    }

    private function seedShips(): void
    {
        // [id => [max_status_points, decay_rate, supply_cost]]
        $data = [
            29 => [20, 0.16, 14],  // frigate1          — ticks_until_lost 125
            49 => [20, 0.10, 25],  // battlecruiser1    — ticks_until_lost 200
            83 => [20, 0.07, 4],   // mediumTransporter — ticks_until_lost ~285
            84 => [20, 0.06, 7],   // largeTransporter  — ticks_until_lost 333
        ];

        foreach ($data as $id => [$msp, $decayRate, $supplyCost]) {
            DB::table('ships')->where('id', $id)->update([
                'max_status_points' => $msp,
                'decay_rate' => $decayRate,
                'supply_cost' => $supplyCost,
            ]);
        }
    }

    private function seedResearches(): void
    {
        // Old research system (legacy IDs) — kept for backward compatibility
        // All: max_status_points = 20, ticks_until_lost = 160 → decay_rate = 0.13
        // Military: higher supply cost (8 vs 5)
        $standard = [33, 34, 39, 72, 73, 74, 76, 79, 80];
        foreach ($standard as $id) {
            DB::table('researches')->where('id', $id)->update([
                'max_status_points' => 20,
                'decay_rate' => 0.13,
                'supply_cost' => 5,
            ]);
        }

        DB::table('researches')->where('id', 81)->update([
            'max_status_points' => 20,
            'decay_rate' => 0.13,
            'supply_cost' => 8,
        ]);

        // Kenntnisse (IDs 90–96) — GDD §10: permanent knowledge, no decay
        $kenntnisse = [90, 91, 92, 93, 94, 95, 96];
        foreach ($kenntnisse as $id) {
            DB::table('researches')->where('id', $id)->update([
                'max_status_points' => 20,
                'decay_rate' => 0,
                'supply_cost' => 0,
            ]);
        }
    }
}
