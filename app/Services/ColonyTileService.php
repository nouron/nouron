<?php

namespace App\Services;

use App\Models\Colony;
use App\Models\ColonyTile;
use App\Services\Techtree\PersonellService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ColonyTileService
{
    public function __construct(
        private readonly PersonellService $personellService,
    ) {}

    public function getTilesForColony(int $colonyId): Collection
    {
        return ColonyTile::where('colony_id', $colonyId)
            ->orderBy('ring')
            ->orderBy('q')
            ->orderBy('r')
            ->get()
            ->map(fn ($t) => $this->transformTile($t));
    }

    public function exploreTile(int $colonyId, int $q, int $r): array
    {
        $tile = ColonyTile::where('colony_id', $colonyId)->where('q', $q)->where('r', $r)->first();

        if (! $tile) {
            return ['ok' => false, 'error' => __('colony.error_tile_not_found')];
        }
        if ($tile->is_explored) {
            return ['ok' => false, 'error' => __('colony.error_already_explored')];
        }

        if (! config('game.bypass.ap_checks') && $this->personellService->getAvailableActionPoints('navigation', $colonyId) < 1) {
            return ['ok' => false, 'error' => __('colony.error_no_nav_ap')];
        }

        $tile->is_explored = true;
        $tile->save();
        if (! config('game.bypass.ap_checks')) {
            $this->personellService->lockActionPoints('navigation', $colonyId, 1);
        }

        return ['ok' => true, 'tile' => $this->transformTile($tile)];
    }

    public function deepScanTile(int $colonyId, int $q, int $r): array
    {
        $tile = ColonyTile::where('colony_id', $colonyId)->where('q', $q)->where('r', $r)->first();

        if (! $tile) {
            return ['ok' => false, 'error' => __('colony.error_tile_not_found')];
        }
        if (! $tile->is_explored) {
            return ['ok' => false, 'error' => __('colony.error_not_explored')];
        }
        if ($tile->event_type === null) {
            return ['ok' => false, 'error' => __('colony.error_no_signal')];
        }
        if ($tile->is_deep_scanned) {
            return ['ok' => false, 'error' => __('colony.error_already_scanned')];
        }

        // Uplink-Station Lv2+ (building_id=54): deep-scan costs 1 Nav-AP instead of 2.
        $uplinkLv = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', (int) config('buildings.uplinkStation.id', 54))
            ->value('level') ?? 0;
        $scanApCost = ($uplinkLv >= 2) ? 1 : 2;

        if (! config('game.bypass.ap_checks') && $this->personellService->getAvailableActionPoints('navigation', $colonyId) < $scanApCost) {
            return ['ok' => false, 'error' => __('colony.error_no_nav_ap_2')];
        }

        $tile->is_deep_scanned = true;
        $tile->save();
        if (! config('game.bypass.ap_checks')) {
            $this->personellService->lockActionPoints('navigation', $colonyId, $scanApCost);
        }

        return ['ok' => true, 'tile' => $this->transformTile($tile)];
    }

    /**
     * Recalculate which terrain tiles belong to the colony zone for a given CC level.
     * Colony zone tiles are also auto-explored (they are part of the settled area).
     * Ring 0 (CC tile) is always colony zone regardless of CC level.
     */
    public function assignColonyZone(int $colonyId, int $ccLevel): void
    {
        $expansion = config('game.colony_zone_expansion', [4, 2, 3, 3, 3]);
        $target = (int) array_sum(array_slice($expansion, 0, max(0, $ccLevel)));

        $maxRing = (int) DB::table('colony_tiles')->where('colony_id', $colonyId)->max('ring');

        // Load all tile types for lookup
        $tileTypes = DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->get(['q', 'r', 'tile_type'])
            ->keyBy(fn ($t) => "{$t->q},{$t->r}")
            ->map(fn ($t) => $t->tile_type)
            ->toArray();

        // Ring 0 (CC) always colony zone
        $colonyZone = [[0, 0]];
        $counted = 0;

        for ($ring = 1; $ring <= $maxRing && $counted < $target; $ring++) {
            foreach ($this->ringCoords($ring) as [$q, $r]) {
                if ($counted >= $target) {
                    break;
                }
                $type = $tileTypes["{$q},{$r}"] ?? null;
                if ($type === null
                    || $type === 'terrain_impassable'
                    || str_starts_with($type, 'regolith_')) {
                    continue;
                }
                $colonyZone[] = [$q, $r];
                $counted++;
            }
        }

        // Reset all tiles
        DB::table('colony_tiles')->where('colony_id', $colonyId)->update(['is_colony_zone' => 0]);

        // Mark colony zone tiles (also auto-explore them)
        foreach ($colonyZone as [$q, $r]) {
            DB::table('colony_tiles')
                ->where('colony_id', $colonyId)
                ->where('q', $q)->where('r', $r)
                ->update(['is_colony_zone' => 1, 'is_explored' => 1]);
        }
    }

    /**
     * Generate a default hex grid for a colony (dev / first-visit convenience).
     * Tiles are deterministic based on q, r, and colony_id.
     */
    public function generateDefaultTiles(Colony $colony, int $maxRing = 3): void
    {
        $colonyId = $colony->id;
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level') ?? 0;

        $rows = [];

        for ($q = -$maxRing; $q <= $maxRing; $q++) {
            $rMin = max(-$maxRing, -$q - $maxRing);
            $rMax = min($maxRing, -$q + $maxRing);
            for ($r = $rMin; $r <= $rMax; $r++) {
                $ring = max(abs($q), abs($r), abs($q + $r));

                $isCC = ($q === 0 && $r === 0);
                $isExplored = $isCC || $ring <= 1;
                $tileType = $isCC ? 'terrain_empty' : $this->pickTileType($q, $r, $colonyId, $ring);

                $resourceAmount = null;
                $resourceMax = null;
                if (str_starts_with($tileType, 'regolith_')) {
                    $resourceMax = match ($tileType) {
                        'regolith_rich' => 800,
                        'regolith_normal' => 500,
                        default => 250,
                    };
                    $resourceAmount = $resourceMax;
                }

                $rows[] = [
                    'colony_id' => $colonyId,
                    'q' => $q,
                    'r' => $r,
                    'ring' => $ring,
                    'tile_type' => $tileType,
                    'event_type' => null,
                    'is_colony_zone' => 0,
                    'is_explored' => $isExplored ? 1 : 0,
                    'is_deep_scanned' => 0,
                    'resource_amount' => $resourceAmount,
                    'resource_max' => $resourceMax,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('colony_tiles')->insertOrIgnore($rows);
        $this->assignColonyZone($colonyId, $ccLevel);
    }

    private function transformTile(ColonyTile $tile): array
    {
        $arr = $tile->toArray();
        $arr['has_signal'] = $tile->event_type !== null && (bool) $tile->is_explored && ! (bool) $tile->is_deep_scanned;
        // Hide the actual event until the tile is deep-scanned (sondiert)
        $arr['event_type'] = $tile->is_deep_scanned ? $tile->event_type : null;

        return $arr;
    }

    private function pickTileType(int $q, int $r, int $colonyId, int $ring = 3): string
    {
        $hash = abs($q * 7 + $r * 13 + $colonyId * 3) % 100;

        // Ring 1: settled core — all buildable, no hazards (hazard mechanic not yet implemented)
        if ($ring <= 1) {
            return 'terrain_empty';
        }

        // Ring 2: colony expansion zone — no regolith, rare hazards and blockers
        if ($ring === 2) {
            if ($hash < 3) {
                return 'terrain_impassable';
            }
            if ($hash < 10) {
                return 'terrain_hazard';
            }

            return 'terrain_empty';
        }

        // Ring 3+: full mix including resource tiles
        if ($hash < 5) {
            return 'terrain_impassable';
        }
        if ($hash < 15) {
            return 'terrain_hazard';
        }
        if ($hash < 35) {
            return 'regolith_poor';
        }
        if ($hash < 55) {
            return 'regolith_normal';
        }
        if ($hash < 65) {
            return 'regolith_rich';
        }

        return 'terrain_empty';
    }

    private function ringCoords(int $ring): array
    {
        if ($ring === 0) {
            return [[0, 0]];
        }

        $coords = [];
        $dirs = [[1, 0], [0, 1], [-1, 1], [-1, 0], [0, -1], [1, -1]];
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
}
