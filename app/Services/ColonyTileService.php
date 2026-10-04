<?php

namespace App\Services;

use App\Console\Commands\GameTick;
use App\Enums\BuildingId;
use App\Models\Colony;
use App\Models\ColonyTile;
use App\Support\RunSeed;
use App\Support\SeededRandom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\Randomizer;

class ColonyTileService
{
    /** Ring-3 "frontier" tile count seeded at Sol 1 — a deliberate half-subset of the full 18-tile ring. */
    private const RING3_FRONTIER_COUNT = 9;

    public function __construct(
        private readonly AdvisorService $advisorService,
        private readonly ProjectBonusService $projectBonusService,
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
            return ['ok' => false, 'error' => 'tile_not_found', 'message' => __('colony.error_tile_not_found')];
        }
        if ($tile->is_explored) {
            return ['ok' => false, 'error' => 'already_explored', 'message' => __('colony.error_already_explored')];
        }

        $baseApCost = (int) (config('game.colony.explore_cost_per_ring')[$tile->ring] ?? config('game.colony.explore_cost_default', 1));
        $apCost = $this->projectBonusService->effectiveNavigationApCost($colonyId, $baseApCost);

        // Lenn's "Navigation-Rabatt" Vier-Ausgänge-Pool outcome (A42) — a
        // granted, unconsumed voucher waives the Nav-AP cost entirely for
        // this call (100% discount, not additive with the cartography
        // discount above — the whole cost simply becomes 0).
        $hasActiveNavVoucher = (bool) DB::table('colony_information_pool_state')
            ->where('colony_id', $colonyId)
            ->where('active_nav_voucher', true)
            ->exists();
        if ($hasActiveNavVoucher) {
            $apCost = 0;
        }

        if (! config('game.bypass.ap_checks') && $this->advisorService->getAvailableActionPoints($colonyId) < $apCost) {
            return ['ok' => false, 'error' => 'no_nav_ap', 'message' => __('colony.error_no_nav_ap')];
        }

        $tile->is_explored = true;
        $tile->save();
        if ($hasActiveNavVoucher) {
            DB::table('colony_information_pool_state')
                ->where('colony_id', $colonyId)
                ->update(['active_nav_voucher' => false]);
        }
        if (! config('game.bypass.ap_checks') && $apCost > 0) {
            $this->advisorService->lockActionPoints($colonyId, $apCost);
        }

        return ['ok' => true, 'tile' => $this->transformTile($tile)];
    }

    public function deepScanTile(int $colonyId, int $q, int $r): array
    {
        $tile = ColonyTile::where('colony_id', $colonyId)->where('q', $q)->where('r', $r)->first();

        if (! $tile) {
            return ['ok' => false, 'error' => 'tile_not_found', 'message' => __('colony.error_tile_not_found')];
        }
        if (! $tile->is_explored) {
            return ['ok' => false, 'error' => 'not_explored', 'message' => __('colony.error_not_explored')];
        }
        if ($tile->event_type === null) {
            return ['ok' => false, 'error' => 'no_signal', 'message' => __('colony.error_no_signal')];
        }
        if ($tile->is_deep_scanned) {
            return ['ok' => false, 'error' => 'already_scanned', 'message' => __('colony.error_already_scanned')];
        }

        // Uplink-Station Lv2+ (building_id=54): deep-scan costs 1 Nav-AP instead of 2.
        $uplinkLv = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', (int) config('buildings.uplinkStation.id', 54))
            ->value('level') ?? 0;
        $scanApCost = ($uplinkLv >= 2) ? 1 : 2;

        if (! config('game.bypass.ap_checks') && $this->advisorService->getAvailableActionPoints($colonyId) < $scanApCost) {
            return ['ok' => false, 'error' => 'no_nav_ap', 'message' => __('colony.error_no_nav_ap_2')];
        }

        $tile->is_deep_scanned = true;
        $tile->save();
        if (! config('game.bypass.ap_checks')) {
            $this->advisorService->lockActionPoints($colonyId, $scanApCost);
        }

        return ['ok' => true, 'tile' => $this->transformTile($tile)];
    }

    /**
     * Recalculate which terrain tiles belong to the colony zone for a given CC level.
     * Colony-zone tiles are buildable but NOT auto-explored — fog is lifted only by
     * exploring (Nav-AP) or building on the tile. Ring 0 (CC tile) is always zone.
     */
    public function assignColonyZone(int $colonyId, int $ccLevel): void
    {
        $colonyZone = $this->computeColonyZoneCoords($colonyId, $ccLevel);

        // Reset all tiles
        DB::table('colony_tiles')->where('colony_id', $colonyId)->update(['is_colony_zone' => 0]);

        // Mark colony zone tiles as buildable. Decoupled from exploration: the zone
        // grants build permission but does NOT auto-lift the fog — a zone tile stays
        // fogged until the player explores it (Nav-AP) or builds on it (settle → see).
        // This keeps "erschließen" (CC grows buildable area) and "erkunden" (Nav-AP
        // reveals the surroundings) as two distinct, separately-communicated axes.
        foreach ($colonyZone as [$q, $r]) {
            DB::table('colony_tiles')
                ->where('colony_id', $colonyId)
                ->where('q', $q)->where('r', $r)
                ->update(['is_colony_zone' => 1]);
        }
    }

    /**
     * Compute the colony-zone tile coordinates for a given CC level (deterministic,
     * ring order, skipping impassable/regolith). Read-only — does not touch the DB.
     * Ring 0 (CC tile) is always included.
     *
     * @return list<array{0:int,1:int}>
     */
    public function computeColonyZoneCoords(int $colonyId, int $ccLevel): array
    {
        $expansion = config('game.colony_zone_expansion', [4, 2, 3, 3, 3]);
        $target = (int) array_sum(array_slice($expansion, 0, max(0, $ccLevel)));

        $maxRing = (int) DB::table('colony_tiles')->where('colony_id', $colonyId)->max('ring');

        $tileTypes = DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->get(['q', 'r', 'tile_type'])
            ->keyBy(fn ($t) => "{$t->q},{$t->r}")
            ->map(fn ($t) => $t->tile_type)
            ->toArray();

        $colonyZone = [[0, 0]]; // Ring 0 (CC) always colony zone
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

        return $colonyZone;
    }

    /**
     * Tile keys ("q,r") that the NEXT CC upgrade would newly add to the colony zone
     * (the delta between the zone at $ccLevel and at $ccLevel + 1). Used to flag the
     * "soon buildable" tiles honestly — only those the CC will actually claim, not
     * every explored tile outside the zone. Empty at max CC level.
     *
     * @return array<string,true> set keyed by "q,r"
     */
    public function nextZoneTileKeys(int $colonyId, int $ccLevel): array
    {
        $keyOf = fn (array $c) => $c[0].','.$c[1];

        $current = [];
        foreach ($this->computeColonyZoneCoords($colonyId, $ccLevel) as $c) {
            $current[$keyOf($c)] = true;
        }

        $next = [];
        foreach ($this->computeColonyZoneCoords($colonyId, $ccLevel + 1) as $c) {
            $key = $keyOf($c);
            if (! isset($current[$key])) {
                $next[$key] = true;
            }
        }

        return $next;
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
            ->where('building_id', BuildingId::CommandCenter->value)
            ->value('level');

        $rows = [];

        for ($q = -$maxRing; $q <= $maxRing; $q++) {
            $rMin = max(-$maxRing, -$q - $maxRing);
            $rMax = min($maxRing, -$q + $maxRing);
            for ($r = $rMin; $r <= $rMax; $r++) {
                $ring = max(abs($q), abs($r), abs($q + $r));

                $isCC = ($q === 0 && $r === 0);
                $isExplored = $isCC || $ring <= 1;
                $tileType = $isCC ? 'terrain_empty' : $this->pickTileType($q, $r, $colonyId, $ring);

                $resourceMax = $this->resourceMaxFor($tileType);
                $resourceAmount = $resourceMax;

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

    /**
     * Fresh resource_max for a regolith tile_type, sourced from config/game.php
     * (single source of truth shared with GameTick's harvester yield formula).
     * Returns null for non-regolith tile types.
     */
    private function resourceMaxFor(string $tileType): ?int
    {
        if (! str_starts_with($tileType, 'regolith_')) {
            return null;
        }

        return (int) (config('game.harvester.resource_max')[$tileType] ?? config('game.harvester.resource_max.regolith_y1_d1', 160));
    }

    /**
     * "≈N Sole bis Erschöpfung" estimate for a regolith tile under active
     * Harvester production (A25, docs/superpowers/specs/2026-08-10-harvester-
     * constant-yield-design.md §2). Computed server-side because the geology
     * bonus and trust multiplier are only reliably known there. An ESTIMATE,
     * not a guarantee — both inputs can change before the tile actually
     * empties, so callers must label it "ca. N Sole" in the UI.
     *
     * Null means "no countdown to show": either the tile is already exhausted
     * or the effective rate is 0 (unconfigured tile type, or a multiplier that
     * zeroes out the yield).
     */
    public function solsRemaining(string $tileType, int $resourceAmount, int $resourceMax, int $geologyLevel, float $trustMultiplier): ?int
    {
        if ($resourceAmount <= 0) {
            return null;
        }

        $baseYield = GameTick::harvesterYield($tileType, $resourceAmount, $resourceMax, $geologyLevel);
        $effectiveRate = (int) round($baseYield * $trustMultiplier);

        if ($effectiveRate <= 0) {
            return null;
        }

        return (int) ceil($resourceAmount / $effectiveRate);
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
        // The run's rng_seed, not the colony id (R5b): ids depend on start order in a
        // shared database — see App\Support\RunSeed.
        $runSeed = RunSeed::forColony($colonyId);
        $hash = abs($q * 7 + $r * 13 + $runSeed * 3) % 100;
        // Independent second hash (different multipliers) so the combo roll
        // does not correlate with the primary roll (A44/H1).
        $comboHash = abs($q * 11 + $r * 17 + $runSeed * 5) % 100;

        return $this->resolveTileType($ring, $hash, $comboHash);
    }

    /**
     * Picks a tile_type from the ring-weighted distribution using a seeded
     * roll (not the deterministic colonyId hash) — randomizes the Sol-1
     * starting environment so every run/reset gets a different layout
     * (roguelike), while still being reproducible for a given seed (A38: a
     * caller that wants the SAME map back — the playtest tool — passes the
     * same seed; real players always get a fresh random seed, see
     * OnboardingService::seedSol1State()). See OnboardingService::seedStartingTiles().
     */
    public function randomTileType(int $ring, int $seed): string
    {
        // A44/H1: a second, independent roll picks the yield/deposit combo
        // when the primary roll lands on regolith. Salted seed keeps both
        // rolls deterministic per $seed without correlating with each other.
        $roll = SeededRandom::int($seed, 0, 99);
        $comboRoll = SeededRandom::int($seed + 1, 0, 99);

        return $this->resolveTileType($ring, $roll, $comboRoll);
    }

    /**
     * Shared weight table for ring-based tile_type selection. $roll and
     * $comboRoll must be in [0, 99] — callers decide whether they come from a
     * deterministic hash (pickTileType) or a true random roll (randomTileType,
     * randomizeOuterRingRows). $comboRoll is only consulted when $roll lands
     * in the regolith band, but callers should always draw it (fixed roll
     * order per tile keeps RNG streams stable across refactors — A44/H1).
     */
    private function resolveTileType(int $ring, int $roll, int $comboRoll = 0): string
    {
        // Ring 1: settled core — all buildable, no hazards (hazard mechanic not yet implemented)
        if ($ring <= 1) {
            return 'terrain_empty';
        }

        // Ring 2: colony expansion zone — no regolith, rare hazards, NO blockers.
        // Ring 2 has no regolith, so a gap in the "bald bebaubar" locks would
        // deterministically reveal a blocker — a UI-assisted trap without any
        // decision. Ring 3+ keeps blockers: there a gap is ambiguous (usually
        // regolith, sometimes rock), which makes revealing it a real gamble
        // (game-designer review 2026-07-11, GDD §16).
        if ($ring === 2) {
            if ($roll < 10) {
                return 'terrain_hazard';
            }

            return 'terrain_empty';
        }

        // Ring 3+: full mix including resource tiles. Bands unchanged since
        // the old poor/normal/rich split (A44/H1 invariant d) — only the
        // regolith band now picks one of 8 yield/deposit combos instead of
        // directly encoding poor/normal/rich.
        if ($roll < 5) {
            return 'terrain_impassable';
        }
        if ($roll < 15) {
            return 'terrain_hazard';
        }
        if ($roll < 65) {
            return $this->pickRegolithCombo($comboRoll);
        }

        return 'terrain_empty';
    }

    /**
     * Picks one of the 8 valid yield/deposit combos from a roll in [0, 99],
     * split into equally-wide buckets. `regolith_y3_d3` (highest yield AND
     * highest deposit) is deliberately excluded — no tile is ever the best on
     * both axes (A44/H1 invariant b).
     */
    private function pickRegolithCombo(int $comboRoll): string
    {
        static $combos = [
            'regolith_y1_d1', 'regolith_y1_d2', 'regolith_y1_d3',
            'regolith_y2_d1', 'regolith_y2_d2', 'regolith_y2_d3',
            'regolith_y3_d1', 'regolith_y3_d2',
        ];

        $index = intdiv(max(0, min(99, $comboRoll)) * count($combos), 100);

        return $combos[$index];
    }

    /**
     * A44/H2: picks two of the 8 regolith combos that form a genuine
     * Pareto-contrast pair — one strictly higher yield tier AND strictly
     * lower deposit tier than the other, so neither dominates. Small
     * rejection-sampling loop over the fixed 8-combo list; a valid pair
     * always exists, so this terminates quickly.
     *
     * @return array{0:string,1:string}
     */
    private function pickH2Pair(Randomizer $rng): array
    {
        static $combos = [
            'regolith_y1_d1', 'regolith_y1_d2', 'regolith_y1_d3',
            'regolith_y2_d1', 'regolith_y2_d2', 'regolith_y2_d3',
            'regolith_y3_d1', 'regolith_y3_d2',
        ];

        do {
            $i = $rng->getInt(0, count($combos) - 1);
            $j = $rng->getInt(0, count($combos) - 1);
        } while ($i === $j || ! $this->isContrastPair($combos[$i], $combos[$j]));

        return [$combos[$i], $combos[$j]];
    }

    /** True if neither combo dominates the other on both the yield and deposit axis. */
    private function isContrastPair(string $a, string $b): bool
    {
        [$yieldA, $depositA] = $this->comboTiers($a);
        [$yieldB, $depositB] = $this->comboTiers($b);

        $yieldCmp = $yieldA <=> $yieldB;
        $depositCmp = $depositA <=> $depositB;

        return $yieldCmp !== 0 && $depositCmp !== 0 && $yieldCmp !== $depositCmp;
    }

    /** @return array{0:int,1:int} [yield tier, deposit tier] parsed from a `regolith_y{n}_d{n}` combo. */
    private function comboTiers(string $combo): array
    {
        preg_match('/^regolith_y(\d)_d(\d)$/', $combo, $matches);

        return [(int) $matches[1], (int) $matches[2]];
    }

    /**
     * Builds randomized Ring-2 (12 tiles) + Ring-3 (9 "frontier" tiles) rows
     * for Sol-1 seeding (no DB write, no colony_id). Ring 0+1 stay fixed in
     * the caller — building placement safety + "no hazards in the core" rule.
     *
     * A44/H2: guarantees exactly TWO is_explored regolith_* tiles among the
     * Ring-3 rows — a genuine Pareto-contrast pair (see pickH2Pair()), not
     * the same fixed two combos every run. This replaces the old single
     * pre-explored "Nexus-Scout" tile: the player now always has a real
     * Rush-vs-Steady tradeoff to pick from at Sol 1, not just one known spot.
     * Onboarding's hint_2 and the Harvester move-mode UI depend on both
     * tiles existing, not on their location or exact combo.
     *
     * Which 9 of the 18 Ring-3 coordinates exist is also randomized per call
     * (not just their content) — otherwise the "frontier" shape would look
     * identical across every run/reset even though tile_type varies.
     *
     * Seeded (A38): the same $seed reproduces the exact same rows — needed
     * so `game:playtest --seeds=X` actually reproduces a run, not just its
     * objective draw. Real players always pass a freshly random seed (see
     * OnboardingService::seedSol1State()), so nothing changes for them.
     *
     * @return list<array{q:int,r:int,ring:int,tile_type:string,is_colony_zone:int,is_explored:int,resource_amount:?int,resource_max:?int}>
     */
    public function randomizeOuterRingRows(int $seed): array
    {
        // One seeded generator per map; rolls are drawn in a fixed order so the
        // same seed always reproduces the same map (A44/T20).
        $rng = SeededRandom::generator($seed);
        $rows = [];

        foreach ($this->ringCoords(2) as [$q, $r]) {
            $roll = $rng->getInt(0, 99);
            $comboRoll = $rng->getInt(0, 99); // discarded — Ring 2 never rolls regolith, but keeps the draw order stable
            $tileType = $this->resolveTileType(2, $roll, $comboRoll);
            $resourceMax = $this->resourceMaxFor($tileType);
            $rows[] = [
                'q' => $q, 'r' => $r, 'ring' => 2,
                'tile_type' => $tileType,
                'is_colony_zone' => 0, 'is_explored' => 0,
                'resource_amount' => $resourceMax, 'resource_max' => $resourceMax,
            ];
        }

        $ring3Coords = $this->ringCoords(3);
        // Seeded Fisher-Yates — replaces shuffle(), which has no seed argument.
        for ($i = count($ring3Coords) - 1; $i > 0; $i--) {
            $j = $rng->getInt(0, $i);
            [$ring3Coords[$i], $ring3Coords[$j]] = [$ring3Coords[$j], $ring3Coords[$i]];
        }
        $ring3Coords = array_slice($ring3Coords, 0, self::RING3_FRONTIER_COUNT);

        $ring3Rows = [];
        foreach ($ring3Coords as [$q, $r]) {
            $roll = $rng->getInt(0, 99);
            $comboRoll = $rng->getInt(0, 99);
            $ring3Rows[] = ['q' => $q, 'r' => $r, 'ring' => 3, 'tile_type' => $this->resolveTileType(3, $roll, $comboRoll)];
        }

        // A44/H2: two distinct indices (no replacement), unconditionally overwritten
        // with a contrast pair — the whole point is that these two combos are always
        // markedly different, not "whatever the regular roll happened to produce".
        $idx1 = $rng->getInt(0, count($ring3Rows) - 1);
        $idx2 = $rng->getInt(0, count($ring3Rows) - 2);
        if ($idx2 >= $idx1) {
            $idx2++;
        }

        [$comboA, $comboB] = $this->pickH2Pair($rng);
        $ring3Rows[$idx1]['tile_type'] = $comboA;
        $ring3Rows[$idx2]['tile_type'] = $comboB;

        foreach ($ring3Rows as $i => $row) {
            $row['is_colony_zone'] = 0;
            $row['is_explored'] = in_array($i, [$idx1, $idx2], true) ? 1 : 0;
            $resourceMax = $this->resourceMaxFor($row['tile_type']);
            $row['resource_amount'] = $resourceMax;
            $row['resource_max'] = $resourceMax;
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Tiles currently occupied by an active Harvester instance (level > 0, placed,
     * not mid-relocation) that carry a regolith resource_max — i.e. the tiles the
     * hex-grid regolith badge and the low-regolith onboarding hint both read.
     * Single source of truth for "which tile is the active Harvester on, and how
     * much regolith does it have left" — kept here so ColonyController::hexview()
     * (tile display) and OnboardingHintService (warning hint) can never disagree
     * on the underlying numbers (see Owner-Playtest-Fund 2026-09-04, GDD §4c).
     *
     * @return Collection<int, \stdClass> each with q, r, tile_type, resource_amount, resource_max
     */
    public function activeHarvesterRegolithTiles(int $colonyId, int $globalTick): Collection
    {
        $activeHarvesterTileKeys = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Harvester->value)
            ->where('level', '>', 0)
            ->whereNotNull('tile_x')
            ->whereNotNull('tile_y')
            ->where(fn ($q) => $q->whereNull('pending_until_tick')->orWhere('pending_until_tick', '<', $globalTick))
            ->get(['tile_x', 'tile_y'])
            ->map(fn ($row) => $row->tile_x.','.$row->tile_y)
            ->flip();

        if ($activeHarvesterTileKeys->isEmpty()) {
            return collect();
        }

        $regolithMaxCfg = config('game.harvester.resource_max', []);

        return DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->get(['q', 'r', 'tile_type', 'resource_amount'])
            ->filter(fn ($tile) => $activeHarvesterTileKeys->has($tile->q.','.$tile->r))
            ->map(function ($tile) use ($regolithMaxCfg) {
                $configMax = (int) ($regolithMaxCfg[$tile->tile_type] ?? 0);
                $tile->resource_max = $configMax;
                $tile->resource_amount = min((int) ($tile->resource_amount ?? $configMax), $configMax);

                return $tile;
            })
            ->filter(fn ($tile) => $tile->resource_max > 0)
            ->values();
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
