<?php

namespace App\Console\Commands;

use App\Services\ColonyTileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Populates a colony with a rich demo state (~80% built out) for visual testing.
 *
 * Run: php artisan colony:seed-demo [colony_id=1]
 *
 * Layout:
 *   Ring 0:   Kommandozentrale (terrain_empty)
 *   Ring 1-2: Kolonie-Zone — terrain tiles only (terrain_empty / hazard), buildings placed
 *   Ring 3:   Exploration-Zone — regolith + events, mostly scouted; Harvester placed here
 *
 * Game-Design rule: Regolith-Tiles are NOT buildable except for the Harvester.
 *                   Regular buildings stand only on terrain tiles.
 */
class ColonySeedDemo extends Command
{
    protected $signature   = 'colony:seed-demo {colony_id=1 : Colony ID to seed}';
    protected $description = 'Seed a colony with a rich ~80% built-out demo state for testing';

    public function __construct(
        private readonly ColonyTileService $tileService,
    ) {
        parent::__construct();
    }

    // Harvester placed on a regolith tile in ring 3
    private const HARVESTER_TILE = [3, 0];

    private const BUILDING_PLACEMENTS = [
        25 => [0,   0],  // CC (ring 0, terrain)
        28 => [0,   1],  // housingComplex (ring 1, terrain)
        30 => [-1,  1],  // depot          (ring 1, terrain)
        31 => [-1,  0],  // sciencelab     (ring 1, terrain)
        41 => [0,  -1],  // bioFacility    (ring 1, terrain)
        44 => [2,   0],  // hangar         (ring 2, terrain)
        52 => [-2,  0],  // bar            (ring 2, terrain)
        27 => [3,   0],  // harvester      (ring 3, regolith — exploration zone)
        // hospital (46), temple (32), denkmal (50) intentionally unplaced — available in Build Mode
    ];

    private const BUILDING_LEVELS = [
        25 => 5,  // CC level 5 for demo (all 15 colony zone tiles unlocked)
        27 => 1,
        28 => 2,
        30 => 3,
        31 => 2,
        41 => 1,
        44 => 1,
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

        $this->tileService->assignColonyZone($colonyId, 5);  // CC Lv5: all 15 colony zone tiles
        $this->info('  ✓ Colony zone assigned (CC Lv5, 15 terrain tiles)');

        $this->assignBuildingTiles($colonyId);
        $this->info('  ✓ Building positions assigned (hospital/temple/denkmal left unplaced)');

        $this->ensurePilotAdvisor($colonyId);
        $this->info('  ✓ Pilot advisor ensured (navigation AP)');

        $this->line('Done.');
        return self::SUCCESS;
    }

    private function generateTiles(int $colonyId): void
    {
        $tiles = [];
        $now   = now();

        // Forced events at specific ring-3 tiles (identified by q,r key)
        $ring3Events = [
            '0,3'   => 'event_ruin',
            '-3,2'  => 'event_crystal',
            '0,-3'  => 'event_cache',
            '2,-3'  => 'event_wreck',
        ];

        for ($ring = 0; $ring <= 3; $ring++) {
            foreach ($this->ringCoords($ring) as [$q, $r]) {
                $seed = abs($q * 7 + $r * 13 + $colonyId * 3);
                $key  = "{$q},{$r}";

                // Explore/scan state per ring
                $isExplored = $ring <= 3;  // all rings fully explored in demo
                $isDeepScanned = match (true) {
                    $ring <= 2  => true,
                    $ring === 3 => ($seed % 3) < 2,   // ~67% deep-scanned
                    default     => false,
                };

                $eventType = null;
                if ($ring === 3 && isset($ring3Events[$key])) {
                    $eventType = $ring3Events[$key];
                }

                [$tileType, $resourceMax] = $this->tileTypeFor($q, $r, $ring, $seed);

                $resourceAmount = 0;
                if ($resourceMax > 0) {
                    $depleted       = 0.15 + ($seed % 46) / 100.0;
                    $resourceAmount = (int) round($resourceMax * (1 - $depleted));
                }

                $tiles[] = [
                    'colony_id'        => $colonyId,
                    'q'                => $q,
                    'r'                => $r,
                    'ring'             => $ring,
                    'tile_type'        => $tileType,
                    'event_type'       => $eventType,
                    'is_colony_zone'   => false,
                    'is_explored'      => $isExplored,
                    'is_deep_scanned'  => $isDeepScanned,
                    'resource_amount'  => $resourceAmount,
                    'resource_max'     => $resourceMax,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
            }
        }

        foreach (array_chunk($tiles, 50) as $chunk) {
            DB::table('colony_tiles')->insert($chunk);
        }
    }

    /**
     * Rings 0–2 are the colony zone: terrain tiles only (no regolith).
     * Ring 3 is the exploration zone: regolith + hazards + impassable.
     * The harvester tile at (3,0) is always regolith_rich.
     */
    private function tileTypeFor(int $q, int $r, int $ring, int $seed): array
    {
        if ($q === 0 && $r === 0) {
            return ['terrain_empty', 0];
        }

        // Force harvester tile to regolith_rich
        if ($q === self::HARVESTER_TILE[0] && $r === self::HARVESTER_TILE[1]) {
            return ['regolith_rich', 80 + ($seed % 41)];
        }

        // Colony zone (rings 1–2): terrain only, no resources
        if ($ring <= 2) {
            $types = ['terrain_empty', 'terrain_empty', 'terrain_empty', 'terrain_hazard', 'terrain_empty', 'terrain_empty'];
            return [$types[$seed % count($types)], 0];
        }

        // Exploration zone (ring 3)
        $types = match ($ring) {
            3 => ['regolith_normal', 'regolith_rich', 'regolith_poor', 'terrain_empty',
                  'terrain_hazard', 'regolith_normal', 'terrain_impassable', 'regolith_poor'],
            default => ['terrain_empty', 'regolith_poor', 'terrain_hazard', 'terrain_impassable',
                        'regolith_normal', 'terrain_empty', 'terrain_impassable', 'regolith_poor'],
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
        // Clear tile assignment for buildings NOT in BUILDING_PLACEMENTS
        // (so they appear as "available to build" in Build Mode)
        $placedIds = array_keys(self::BUILDING_PLACEMENTS);
        DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->whereNotIn('building_id', $placedIds)
            ->update(['tile_x' => null, 'tile_y' => null, 'level' => 0, 'ap_spend' => 0]);

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

    private function ensurePilotAdvisor(int $colonyId): void
    {
        $pilotId = (int) config('advisors.pilot.id');
        $userId  = (int) DB::table('glx_colonies')->where('id', $colonyId)->value('user_id');

        DB::table('advisors')->insertOrIgnore([
            'user_id'                => $userId,
            'personell_id'           => $pilotId,
            'colony_id'              => $colonyId,
            'rank'                   => 1,
            'active_ticks'           => 0,
            'unavailable_until_tick' => null,
        ]);
    }
}
