<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a "~80% built out" demo colony state for development/UI testing.
 *
 * Deletes existing colony_tiles for the colony, regenerates all 37 tiles
 * (rings 0-3) with realistic exploration/scan state, and assigns the
 * colony's existing buildings to specific tile coordinates.
 *
 * Usage: php artisan colony:seed-demo [colony_id]   (default: 1)
 */
class ColonySeedDemo extends Command
{
    protected $signature = 'colony:seed-demo {colony_id=1}';
    protected $description = 'Seed a demo built-out colony state for UI testing (dev only)';

    public function handle(): int
    {
        $colonyId = (int) $this->argument('colony_id');

        if (! DB::table('glx_colonies')->where('id', $colonyId)->exists()) {
            $this->error("Colony {$colonyId} not found.");
            return 1;
        }

        DB::table('colony_tiles')->where('colony_id', $colonyId)->delete();

        $tiles = $this->buildTiles($colonyId);
        DB::table('colony_tiles')->insert($tiles);
        $this->info(count($tiles) . ' tiles inserted.');

        $placed = $this->placeBuildingsOnTiles($colonyId);
        $this->info("{$placed} buildings placed on tiles.");

        return 0;
    }

    // ── Tile generation ───────────────────────────────────────────────────────

    private function buildTiles(int $colonyId): array
    {
        $maxRing = 3;
        $rows    = [];

        // Tiles that get deep-scanned with events (ring 2 only)
        $eventTiles = [
            [ 0,  2, 'event_ruin'],
            [-2,  1, 'event_crystal'],
            [ 0, -2, 'event_cache'],
            [ 2, -2, 'event_wreck'],
        ];
        $eventMap = [];
        foreach ($eventTiles as [$eq, $er, $etype]) {
            $eventMap["{$eq},{$er}"] = $etype;
        }

        // Ring 3 tiles that are NOT yet unlocked (8 of 18 stay locked)
        $lockedRing3 = [
            [ 3,  0], [ 3, -1], [ 3, -2], [ 3, -3],
            [ 2,  1], [ 1,  2], [ 0,  3], [-1,  3],
        ];
        $lockedSet = [];
        foreach ($lockedRing3 as [$lq, $lr]) {
            $lockedSet["{$lq},{$lr}"] = true;
        }

        // Ring 3 tiles that are unlocked but not yet explored (4 of the 10 remaining)
        $unexploredRing3 = [
            [-2,  3], [-3,  3], [-3,  2],  [ 2, -3],
        ];
        $unexploredSet = [];
        foreach ($unexploredRing3 as [$uq, $ur]) {
            $unexploredSet["{$uq},{$ur}"] = true;
        }

        for ($q = -$maxRing; $q <= $maxRing; $q++) {
            $rMin = max(-$maxRing, -$q - $maxRing);
            $rMax = min($maxRing,  -$q + $maxRing);

            for ($r = $rMin; $r <= $rMax; $r++) {
                $ring = max(abs($q), abs($r), abs($q + $r));
                $key  = "{$q},{$r}";
                $isCC = ($q === 0 && $r === 0);

                // Unlock state
                $isRingUnlocked = match ($ring) {
                    0, 1, 2 => true,
                    default  => ! isset($lockedSet[$key]),
                };

                // Exploration state
                $isExplored = match (true) {
                    $ring <= 2                                  => true,
                    $ring === 3 && ! isset($lockedSet[$key])
                                && ! isset($unexploredSet[$key]) => true,
                    default                                      => false,
                };

                // Deep scan: all ring 0+1, event tiles in ring 2, a few ring 3
                $isDeepScanned = $isExplored && ($ring <= 1 || isset($eventMap[$key]));

                $eventType = $isDeepScanned ? ($eventMap[$key] ?? null) : null;

                // Tile type
                $tileType = $isCC ? 'terrain_empty' : $this->pickTileType($q, $r, $colonyId);

                // Resource deposit
                $resourceMax = $resourceAmount = null;
                if (str_starts_with($tileType, 'regolith_')) {
                    $resourceMax = match ($tileType) {
                        'regolith_rich'   => 800,
                        'regolith_normal' => 500,
                        default           => 250,
                    };
                    // ~80% depleted for "built out" feel
                    $hash           = abs($q * 11 + $r * 17 + $colonyId * 5) % 100;
                    $resourceAmount = (int) ($resourceMax * (0.15 + ($hash / 100) * 0.45));
                }

                $rows[] = [
                    'colony_id'        => $colonyId,
                    'q'                => $q,
                    'r'                => $r,
                    'ring'             => $ring,
                    'tile_type'        => $tileType,
                    'event_type'       => $eventType,
                    'is_ring_unlocked' => $isRingUnlocked ? 1 : 0,
                    'is_explored'      => $isExplored    ? 1 : 0,
                    'is_deep_scanned'  => $isDeepScanned ? 1 : 0,
                    'resource_amount'  => $resourceAmount,
                    'resource_max'     => $resourceMax,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }
        }

        return $rows;
    }

    private function pickTileType(int $q, int $r, int $colonyId): string
    {
        $hash = abs($q * 7 + $r * 13 + $colonyId * 3) % 100;

        if ($hash < 5)  return 'terrain_impassable';
        if ($hash < 15) return 'terrain_hazard';
        if ($hash < 35) return 'regolith_poor';
        if ($hash < 55) return 'regolith_normal';
        if ($hash < 65) return 'regolith_rich';
        return 'terrain_empty';
    }

    // ── Building placement ────────────────────────────────────────────────────

    private function placeBuildingsOnTiles(int $colonyId): int
    {
        // Fixed placement map: building_id → [tile_x (q), tile_y (r)]
        // Ring 1 = production/civil core; Ring 2 = expanded facilities
        $placement = [
            25 => null,          // CC always at (0,0), matched by code, no tile_x/tile_y needed
            27 => [ 1,  0],      // Erzmine          — ring 1
            28 => [ 0,  1],      // Wohnkomplex       — ring 1
            30 => [-1,  1],      // Depot             — ring 1
            31 => [-1,  0],      // Forschungslabor   — ring 1
            41 => [ 0, -1],      // Silikatmine       — ring 1
            42 => [ 1, -1],      // Wasserextraktor   — ring 1
            44 => [ 2,  0],      // Ziviler Raumhafen — ring 2
            46 => [ 1,  1],      // Krankenhaus       — ring 2
            32 => [ 2, -1],      // Tempel            — ring 2
            52 => [-2,  0],      // Bar               — ring 2
            50 => [-1, -1],      // Denkmal           — ring 2
            65 => [ 1, -2],      // Recyclingstation  — ring 2
            68 => [-2,  1],      // Militär Raumhafen — ring 2
        ];

        $placed = 0;
        foreach ($placement as $buildingId => $coords) {
            if ($coords === null) continue;

            [$tx, $ty] = $coords;
            $updated = DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', $buildingId)
                ->update(['tile_x' => $tx, 'tile_y' => $ty]);

            if ($updated) $placed++;
        }

        return $placed;
    }
}
