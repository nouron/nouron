<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Populates a colony with a rich demo state (~80% built out) for visual testing.
 *
 * Run: php artisan colony:seed-demo [colony_id=1]
 *
 * - Deletes and regenerates all colony_tiles (rings 0–3, 37 tiles)
 * - Ring 0+1: fully unlocked, explored, deep-scanned
 * - Ring 2: fully unlocked, explored, 4 events, regolith partially depleted
 * - Ring 3: 10/18 unlocked, 6 explored (frontier)
 * - Assigns tile positions (tile_x/tile_y) to all 11 canonical buildings
 * - Resets building levels to sane values (CC at level 3)
 */
class ColonySeedDemo extends Command
{
    protected $signature   = 'colony:seed-demo {colony_id=1 : Colony ID to seed}';
    protected $description = 'Seed a colony with a rich ~80% built-out demo state for testing';

    private const BUILDING_PLACEMENTS = [
        25 => [0,  0],  // CC at origin
        27 => [1,  0],  // harvester
        28 => [0,  1],  // housingComplex
        30 => [-1, 1],  // depot
        31 => [-1, 0],  // sciencelab
        41 => [0, -1],  // bioFacility
        44 => [2,  0],  // hangar (ring 2, needs CC lv2)
        46 => [1,  1],  // hospital (ring 2)
        32 => [2, -1],  // temple (ring 2)
        52 => [-2, 0],  // bar (ring 2)
        50 => [-1,-1],  // denkmal (ring 2)
    ];

    private const BUILDING_LEVELS = [
        25 => 3,
        27 => 1,
        28 => 2,
        30 => 3,
        31 => 2,
        32 => 1,
        41 => 1,
        44 => 1,
        46 => 1,
        50 => 1,
        52 => 1,
    ];

    public function handle(): int
    {
        $colonyId = (int) $this->argument('colony_id');

        if (!DB::table('glx_colonies')->where('id', $colonyId)->exists()) {
            $this->error("Colony {$colonyId} not found.");
            return self::FAILURE;
        }

        $this->info("Seeding demo state for colony {$colonyId}...");

        DB::table('colony_tiles')->where('colony_id', $colonyId)->delete();

        $this->generateTiles($colonyId);
        $this->info('  ✓ Tiles generated (rings 0–3, 37 tiles)');

        $this->assignBuildingTiles($colonyId);
        $this->info('  ✓ Building positions assigned');

        $this->line('Done.');
        return self::SUCCESS;
    }

    private function generateTiles(int $colonyId): void
    {
        $tiles = [];
        $now   = now();

        $ring2Events = [
            '0,2'   => 'event_ruin',
            '-2,1'  => 'event_crystal',
            '0,-2'  => 'event_cache',
            '2,-2'  => 'event_wreck',
        ];

        for ($ring = 0; $ring <= 3; $ring++) {
            foreach ($this->ringCoords($ring) as [$q, $r]) {
                $seed = abs($q * 7 + $r * 13 + $colonyId * 3);

                $isRingUnlocked  = $ring <= 2 || ($seed % 18) < 10;
                $isExplored      = $ring <= 2 || ($ring === 3 && $isRingUnlocked && ($seed % 6) < 2);
                $isDeepScanned   = $ring <= 1 || ($ring === 2);

                $eventType = null;
                if ($ring === 2 && isset($ring2Events["{$q},{$r}"])) {
                    $eventType = $ring2Events["{$q},{$r}"];
                }

                [$tileType, $resourceMax] = $this->tileTypeFor($q, $r, $ring, $seed, $colonyId);

                $resourceAmount = 0;
                if ($resourceMax > 0) {
                    $depleted = 0.15 + ($seed % 46) / 100.0;
                    $resourceAmount = (int) round($resourceMax * (1 - $depleted));
                }

                $tiles[] = [
                    'colony_id'       => $colonyId,
                    'q'               => $q,
                    'r'               => $r,
                    'ring'            => $ring,
                    'tile_type'       => $tileType,
                    'event_type'      => $eventType,
                    'is_ring_unlocked'=> $isRingUnlocked,
                    'is_explored'     => $isExplored,
                    'is_deep_scanned' => $isDeepScanned,
                    'resource_amount' => $resourceAmount,
                    'resource_max'    => $resourceMax,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
        }

        foreach (array_chunk($tiles, 50) as $chunk) {
            DB::table('colony_tiles')->insert($chunk);
        }
    }

    private function tileTypeFor(int $q, int $r, int $ring, int $seed, int $colonyId): array
    {
        if ($q === 0 && $r === 0) {
            return ['terrain_empty', 0];
        }

        $types = match ($ring) {
            1 => ['terrain_empty', 'regolith_normal', 'regolith_rich', 'terrain_empty', 'regolith_poor', 'terrain_hazard'],
            2 => ['regolith_normal', 'terrain_empty', 'regolith_poor', 'regolith_rich', 'terrain_hazard', 'terrain_impassable'],
            3 => ['terrain_empty', 'regolith_poor', 'terrain_hazard', 'terrain_impassable', 'regolith_normal', 'terrain_empty'],
            default => ['terrain_empty'],
        };

        $type = $types[$seed % count($types)];

        $resourceMax = match ($type) {
            'regolith_rich'   => 80 + ($seed % 41),
            'regolith_normal' => 40 + ($seed % 31),
            'regolith_poor'   => 10 + ($seed % 21),
            default           => 0,
        };

        return [$type, $resourceMax];
    }

    private function ringCoords(int $ring): array
    {
        if ($ring === 0) {
            return [[0, 0]];
        }

        $coords = [];
        $dirs   = [[1,0],[0,1],[-1,1],[-1,0],[0,-1],[1,-1]];
        $q = $ring;
        $r = 0;

        for ($side = 0; $side < 6; $side++) {
            for ($step = 0; $step < $ring; $step++) {
                $coords[] = [$q, $r];
                [$dq, $dr] = $dirs[($side + 2) % 6];
                $q += $dq;
                $r += $dr;
            }
        }

        return $coords;
    }

    private function assignBuildingTiles(int $colonyId): void
    {
        foreach (self::BUILDING_PLACEMENTS as $buildingId => [$tileX, $tileY]) {
            $level = self::BUILDING_LEVELS[$buildingId] ?? 1;

            DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', $buildingId)
                ->update([
                    'tile_x'        => $tileX,
                    'tile_y'        => $tileY,
                    'level'         => $level,
                    'status_points' => 16,
                    'ap_spend'      => 0,
                ]);
        }
    }
}
