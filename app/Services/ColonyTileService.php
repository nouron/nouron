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
            ->map(fn($t) => $this->transformTile($t));
    }

    public function exploreTile(int $colonyId, int $q, int $r): array
    {
        $tile = ColonyTile::where('colony_id', $colonyId)->where('q', $q)->where('r', $r)->first();

        if (!$tile)                   return ['ok' => false, 'error' => __('colony.error_tile_not_found')];
        if (!$tile->is_ring_unlocked) return ['ok' => false, 'error' => __('colony.error_ring_locked')];
        if ($tile->is_explored)       return ['ok' => false, 'error' => __('colony.error_already_explored')];

        if ($this->personellService->getAvailableActionPoints('navigation', $colonyId) < 1) {
            return ['ok' => false, 'error' => __('colony.error_no_nav_ap')];
        }

        $tile->is_explored = true;
        $tile->save();
        $this->personellService->lockActionPoints('navigation', $colonyId, 1);

        return ['ok' => true, 'tile' => $this->transformTile($tile)];
    }

    public function deepScanTile(int $colonyId, int $q, int $r): array
    {
        $tile = ColonyTile::where('colony_id', $colonyId)->where('q', $q)->where('r', $r)->first();

        if (!$tile)                    return ['ok' => false, 'error' => __('colony.error_tile_not_found')];
        if (!$tile->is_explored)       return ['ok' => false, 'error' => __('colony.error_not_explored')];
        if ($tile->event_type === null) return ['ok' => false, 'error' => __('colony.error_no_signal')];
        if ($tile->is_deep_scanned)    return ['ok' => false, 'error' => __('colony.error_already_scanned')];

        if ($this->personellService->getAvailableActionPoints('navigation', $colonyId) < 2) {
            return ['ok' => false, 'error' => __('colony.error_no_nav_ap_2')];
        }

        $tile->is_deep_scanned = true;
        $tile->save();
        $this->personellService->lockActionPoints('navigation', $colonyId, 2);

        return ['ok' => true, 'tile' => $this->transformTile($tile)];
    }

    private function transformTile(ColonyTile $tile): array
    {
        $arr = $tile->toArray();
        $arr['has_signal'] = $tile->event_type !== null && (bool) $tile->is_explored && !(bool) $tile->is_deep_scanned;
        // Hide the actual event until the tile is deep-scanned (sondiert)
        $arr['event_type'] = $tile->is_deep_scanned ? $tile->event_type : null;
        return $arr;
    }

    /**
     * Generate a default hex grid for a colony (dev / first-visit convenience).
     * Tiles are deterministic based on q, r, and colony_id.
     */
    public function generateDefaultTiles(Colony $colony, int $maxRing = 3): void
    {
        $colonyId = $colony->id;
        $ccLevel  = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', 25)
            ->value('level') ?? 0;

        $rows = [];

        for ($q = -$maxRing; $q <= $maxRing; $q++) {
            $rMin = max(-$maxRing, -$q - $maxRing);
            $rMax = min($maxRing, -$q + $maxRing);
            for ($r = $rMin; $r <= $rMax; $r++) {
                $ring = max(abs($q), abs($r), abs($q + $r));

                $isCC            = ($q === 0 && $r === 0);
                $isRingUnlocked  = $ring <= max(1, $ccLevel);
                $isExplored      = $ring <= 1;

                if ($isCC) {
                    $tileType = 'terrain_empty';
                } else {
                    $tileType = $this->pickTileType($q, $r, $colonyId);
                }

                $resourceAmount = null;
                $resourceMax    = null;
                if (str_starts_with($tileType, 'regolith_')) {
                    $resourceMax    = match ($tileType) {
                        'regolith_rich'   => 800,
                        'regolith_normal' => 500,
                        default           => 250,
                    };
                    $resourceAmount = $resourceMax;
                }

                $rows[] = [
                    'colony_id'       => $colonyId,
                    'q'               => $q,
                    'r'               => $r,
                    'ring'            => $ring,
                    'tile_type'       => $tileType,
                    'event_type'      => null,
                    'is_ring_unlocked' => $isRingUnlocked ? 1 : 0,
                    'is_explored'     => $isExplored ? 1 : 0,
                    'is_deep_scanned' => 0,
                    'resource_amount' => $resourceAmount,
                    'resource_max'    => $resourceMax,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
        }

        DB::table('colony_tiles')->insertOrIgnore($rows);
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
}
