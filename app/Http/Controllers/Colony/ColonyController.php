<?php

namespace App\Http\Controllers\Colony;

use App\Enums\BuildingId;
use App\Http\Controllers\BaseController;
use App\Models\Advisor;
use App\Models\Colony;
use App\Services\ColonyService;
use App\Services\ColonyTileService;
use App\Services\EventService;
use App\Services\MerchantService;
use App\Services\OnboardingHintService;
use App\Services\OnboardingTriggerService;
use App\Services\ResourcesService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ColonyController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly ColonyService $colonyService,
        private readonly ColonyTileService $tileService,
        private readonly PersonellService $personellService,
        private readonly OnboardingHintService $hintService,
        private readonly OnboardingTriggerService $onboardingTriggerService,
        private readonly MerchantService $merchantService,
        private readonly EventService $eventService,
        private readonly ResourcesService $resourcesService,
    ) {
        parent::__construct($tick);
    }

    // ── Build-cost helpers ──────────────────────────────────────────────────────

    /** Regolith resource id (colony_resources). */
    private const RES_REGOLITH = 3;

    /**
     * Buildings that count as path choices (sciencelab, hangar, bar).
     * At CC Lv2 the player may place one; each additional CC level unlocks one more.
     */
    private const PATH_BUILDING_IDS = [31, 44, 52];

    /**
     * One-time erect cost for a building, as [resource_id => amount].
     * Canonical source: config/buildings.php `build_cost`. CC + Harvester have none.
     *
     * @return array<int, int>
     */
    private function buildCostFor(int $buildingId): array
    {
        $cfg = collect(config('buildings'))->firstWhere('id', $buildingId);

        return array_map('intval', $cfg['build_cost'] ?? []);
    }

    /**
     * Regolith consumed when a building completes a level-up (flat, no escalation).
     * Rules: CommandCenter scales as target_level × cc_upgrade_regolith_per_level;
     * Harvester is free (bootstrap); all others = 25 % of build_cost Regolith (min 10).
     */
    private function levelupRegolithFor(int $buildingId, int $targetLevel): int
    {
        if ($buildingId === BuildingId::Harvester->value) {
            return 0;
        }

        if ($buildingId === BuildingId::CommandCenter->value) {
            $perLevel = (int) (collect(config('buildings'))->firstWhere('id', $buildingId)['cc_upgrade_regolith_per_level'] ?? 30);

            return $targetLevel * $perLevel;
        }

        $erectRegolith = $this->buildCostFor($buildingId)[self::RES_REGOLITH] ?? 0;
        if ($erectRegolith <= 0) {
            return 0;
        }

        return max(10, (int) round($erectRegolith * 0.25));
    }

    public function hexview(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $tiles = $this->tileService->getTilesForColony($colony->id);

        if ($tiles->isEmpty()) {
            $this->tileService->generateDefaultTiles($colony);
            $tiles = $this->tileService->getTilesForColony($colony->id);
        }

        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->value('level') ?? 0;

        // Flag the tiles the NEXT CC upgrade will actually claim ("soon buildable"),
        // so the lock badge only marks real future colony zone — not every explored
        // tile outside the zone (most of which the CC never reaches).
        $nextZoneKeys = $this->tileService->nextZoneTileKeys($colony->id, $ccLevel);
        $tiles = $tiles->map(function ($tile) use ($nextZoneKeys) {
            $tile['next_zone'] = isset($nextZoneKeys[$tile['q'].','.$tile['r']]);

            return $tile;
        });

        $globalTick = $this->getTick();

        $buildings = DB::table('colony_buildings')
            ->join('buildings', 'colony_buildings.building_id', '=', 'buildings.id')
            ->where('colony_buildings.colony_id', $colony->id)
            ->select(
                'colony_buildings.building_id',
                'colony_buildings.instance_id',
                'colony_buildings.level',
                'colony_buildings.status_points',
                'colony_buildings.ap_spend',
                'colony_buildings.tile_x',
                'colony_buildings.tile_y',
                'colony_buildings.pending_until_tick',
                'buildings.name as building_key',
                'buildings.max_level',
                'buildings.ap_for_levelup',
                'buildings.max_status_points',
            )
            ->get()
            ->map(function ($b) use ($globalTick) {
                $b->label = __('techtree.'.$b->building_key);
                $b->image_slug = self::buildingImageSlug($b->building_key);
                $b->in_transit = $b->pending_until_tick !== null && (int) $b->pending_until_tick >= $globalTick;
                $b->levelup_cost = $this->levelupRegolithFor((int) $b->building_id, (int) $b->level + 1);

                return $b;
            });

        $navAp = $this->personellService->getAvailableActionPoints('navigation', $colony->id);
        $constructionAp = $this->personellService->getAvailableActionPoints('construction', $colony->id);
        $researchAp = $this->personellService->getAvailableActionPoints('research', $colony->id);
        $economyAp = $this->personellService->getAvailableActionPoints('economy', $colony->id);
        $strategyAp = $this->personellService->getAvailableActionPoints('strategy', $colony->id);
        $activeHint = $this->resolveHint($colony->id);

        $fireds = json_decode(DB::table('user_preferences')->where('user_id', Auth::id())->value('fired_triggers') ?? '[]', true) ?? [];
        $supplyCapFull = in_array('supply_cap_full', $fireds);

        $trust = (int) (DB::table('colony_resources')->where('colony_id', $colony->id)->where('resource_id', 12)->value('amount') ?? 0);
        // Build-chip affordability check (greys out unaffordable buildings).
        $regolith = (int) (DB::table('colony_resources')->where('colony_id', $colony->id)->where('resource_id', 3)->value('amount') ?? 0);
        $werkstoffe = (int) (DB::table('colony_resources')->where('colony_id', $colony->id)->where('resource_id', 4)->value('amount') ?? 0);
        $freeSupply = $this->resourcesService->getFreeSupply($colony->id);
        $currentSol = $this->currentSol();
        $solLimit = (int) config('game.run.tick_limit', 100);

        $merchantVisit = $this->merchantService->getActiveVisit($colony->id, $globalTick);
        $merchantItems = $merchantVisit
            ? $this->merchantService->getItemsForVisit($merchantVisit->id)->values()->toArray()
            : [];

        $phaseProgress = $this->computePhaseProgress($colony);

        return view('colony.hexview', compact('colony', 'tiles', 'ccLevel', 'buildings', 'navAp', 'constructionAp', 'researchAp', 'economyAp', 'strategyAp', 'activeHint', 'supplyCapFull', 'trust', 'regolith', 'werkstoffe', 'freeSupply', 'currentSol', 'solLimit', 'merchantVisit', 'merchantItems', 'phaseProgress'));
    }

    // ── Tile actions ──────────────────────────────────────────────────────────

    public function exploreTile(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => 'required|integer', 'r' => 'required|integer']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $result = $this->tileService->exploreTile($colony->id, (int) $data['q'], (int) $data['r']);

        if ($result['ok']) {
            $this->eventService->createEvent([
                'user' => Auth::id(),
                'tick' => $this->getTick(),
                'event' => 'colony.tile_explored',
                'area' => 'colony',
                'parameters' => json_encode(['colony_id' => $colony->id]),
            ]);
        }

        $extra = $result['ok'] ? [...$this->currentAp($colony->id), 'activeHint' => $this->resolveHint($colony->id)] : [];

        return response()->json([...$result, ...$extra]);
    }

    public function deepScanTile(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => 'required|integer', 'r' => 'required|integer']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $result = $this->tileService->deepScanTile($colony->id, (int) $data['q'], (int) $data['r']);

        if ($result['ok']) {
            $this->eventService->createEvent([
                'user' => Auth::id(),
                'tick' => $this->getTick(),
                'event' => 'colony.tile_deep_scanned',
                'area' => 'colony',
                'parameters' => json_encode(['colony_id' => $colony->id]),
            ]);
        }

        $extra = $result['ok'] ? [...$this->currentAp($colony->id), 'activeHint' => $this->resolveHint($colony->id)] : [];

        return response()->json([...$result, ...$extra]);
    }

    // ── Building actions ──────────────────────────────────────────────────────

    public function availableBuildings(): JsonResponse
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', BuildingId::CommandCenter->value)->value('level') ?? 0;

        $placedCounts = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->whereNotNull('tile_x')
            ->selectRaw('building_id, COUNT(*) as cnt')
            ->groupBy('building_id')
            ->pluck('cnt', 'building_id')
            ->toArray();

        $agrardomPlaced = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 41)
            ->whereNotNull('tile_x')
            ->exists();

        $buildings = DB::table('buildings')
            ->select('id', 'name', 'ap_for_levelup', 'max_status_points', 'max_level',
                'required_building_id', 'required_building_level', 'is_instanced', 'supply_cost')
            ->get()
            ->filter(function ($b) use ($ccLevel, $placedCounts, $agrardomPlaced) {
                if ($b->id === BuildingId::CommandCenter->value) {
                    return false;
                }  // CC — already exists
                if ($b->id === BuildingId::Harvester->value) {
                    return false;
                }  // Harvester — regolith placement only
                $count = $placedCounts[$b->id] ?? 0;
                if ($b->is_instanced) {
                    if ($count >= ($b->max_level ?? PHP_INT_MAX)) {
                        return false;
                    }
                } else {
                    if ($count > 0) {
                        return false;
                    }
                }
                if ($b->required_building_id === BuildingId::CommandCenter->value && $ccLevel < (int) ($b->required_building_level ?? 1)) {
                    return false;
                }
                if (in_array($b->id, self::PATH_BUILDING_IDS, true) && ! $agrardomPlaced) {
                    return false;
                }

                return true;
            })
            ->map(fn ($b) => [
                'building_id' => $b->id,
                'key' => $b->name,
                'label' => __('techtree.'.$b->name),
                'description' => __('buildings.'.preg_replace('/^building_/', '', $b->name).'_desc'),
                'ap_for_levelup' => $b->ap_for_levelup,
                'max_level' => $b->max_level,
                'max_status_points' => $b->max_status_points,
                'is_instanced' => (bool) $b->is_instanced,
                'supply_cost' => (int) $b->supply_cost,
                'build_cost' => $this->buildCostFor($b->id),   // [resource_id => amount]
            ])
            ->values();

        return response()->json(['buildings' => $buildings]);
    }

    public function placeBuilding(Request $request): JsonResponse
    {
        $data = $request->validate([
            'building_id' => 'required|integer',
            'q' => 'required|integer',
            'r' => 'required|integer',
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        $tile = DB::table('colony_tiles')
            ->where('colony_id', $colony->id)
            ->where('q', $data['q'])
            ->where('r', $data['r'])
            ->first();

        if (! $tile) {
            return response()->json(['ok' => false, 'error' => __('colony.error_tile_not_found')]);
        }
        $isHarvester = (int) $data['building_id'] === BuildingId::Harvester->value;

        if ($isHarvester) {
            // Harvester relocates to an explored regolith tile in the exploration zone (ring 3+).
            if (! $tile->is_explored) {
                return response()->json(['ok' => false, 'error' => __('colony.error_not_explored')]);
            }
            if (! str_starts_with($tile->tile_type, 'regolith_')) {
                return response()->json(['ok' => false, 'error' => __('colony.error_harvester_needs_regolith')]);
            }
        } else {
            // Regular buildings need only colony-zone permission. The zone is no longer
            // auto-explored (see ColonyTileService::assignColonyZone) — building on a
            // still-fogged zone tile is allowed and reveals it ("settle → see").
            if (! $tile->is_colony_zone) {
                return response()->json(['ok' => false, 'error' => __('colony.error_tile_outside_colony')]);
            }
            if (! str_starts_with($tile->tile_type, 'terrain_') || $tile->tile_type === 'terrain_impassable') {
                return response()->json(['ok' => false, 'error' => __('colony.error_tile_not_buildable')]);
            }
        }

        $occupied = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('tile_x', $data['q'])
            ->where('tile_y', $data['r'])
            ->exists();
        if ($occupied) {
            return response()->json(['ok' => false, 'error' => __('colony.error_tile_occupied')]);
        }

        $building = DB::table('buildings')->where('id', $data['building_id'])->first();
        if (! $building) {
            return response()->json(['ok' => false, 'error' => __('colony.error_building_not_found')]);
        }

        // Path-gate: sciencelab/hangar/bar may only be placed when CC level allows.
        // Harvester is marked is_instanced=1 in schema but has exactly one instance per colony
        // and must always be moved (UPDATE), never duplicated (INSERT).
        $existingBuilding = ($isHarvester || ! $building->is_instanced)
            ? DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', $data['building_id'])
                ->first()
            : null;

        $isHarvesterMove = $isHarvester
            && $existingBuilding !== null
            && $existingBuilding->tile_x !== null;

        if ($isHarvesterMove
                && $existingBuilding->pending_until_tick !== null
                && (int) $existingBuilding->pending_until_tick >= $this->getTick()) {
            return response()->json(['ok' => false, 'error' => __('colony.error_harvester_in_transit')]);
        }

        // Agrardom gate: path buildings require Agrardom (41) to be placed first.
        // Agrardom is a hard prerequisite for CC Lv2 — building a path building before
        // Agrardom would leave the player unable to advance.
        if (in_array((int) $data['building_id'], self::PATH_BUILDING_IDS, true)) {
            $agrardomPlaced = DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', 41)
                ->whereNotNull('tile_x')
                ->exists();
            if (! $agrardomPlaced) {
                return response()->json(['ok' => false, 'error' => __('colony.error_agrardom_required')]);
            }
        }

        $apCost = $isHarvesterMove
            ? max(1, $this->hexDistance((int) $existingBuilding->tile_x, (int) $existingBuilding->tile_y, (int) $data['q'], (int) $data['r']))
            : 1;

        if (! config('game.bypass.ap_checks') && $this->personellService->getConstructionPoints($colony->id) < $apCost) {
            return response()->json([
                'ok' => false,
                'error' => 'ap_limit',
                'ap_type' => 'construction',
                'current' => $this->personellService->getConstructionPoints($colony->id),
                'message' => __('colony.onboarding_trigger_ap_limit'),
            ]);
        }

        // Resource + supply gate for regular buildings (the harvester relocates for free,
        // and CC/Harvester carry no build_cost — bootstrap exemption). Checked before any
        // DB write so a failed gate leaves the colony untouched.
        $buildCost = $isHarvester ? [] : $this->buildCostFor((int) $data['building_id']);

        if (! $isHarvester) {
            if (! config('game.bypass.resource_costs') && $buildCost !== []) {
                $costs = [];
                foreach ($buildCost as $resourceId => $amount) {
                    $costs[] = ['resource_id' => $resourceId, 'amount' => $amount];
                }
                if (! $this->resourcesService->check($costs, $colony->id)) {
                    return response()->json([
                        'ok' => false,
                        'error' => 'resource_limit',
                        'message' => __('colony.error_insufficient_resources'),
                        'cost' => $buildCost,
                    ]);
                }
            }

            // Supply is a cap, not a stockpile: a building may only be erected when the
            // free cap covers its ongoing supply_cost (§6). Nothing is deducted here.
            if (! config('game.bypass.supply_checks')
                && (int) ($building->supply_cost ?? 0) > 0
                && $this->resourcesService->getFreeSupply($colony->id) < (int) $building->supply_cost) {
                return response()->json([
                    'ok' => false,
                    'error' => 'supply_limit',
                    'message' => __('colony.onboarding_trigger_supply_full'),
                ]);
            }
        }

        // Building on a still-fogged colony-zone tile reveals it (settle → see).
        if (! $tile->is_explored) {
            DB::table('colony_tiles')
                ->where('colony_id', $colony->id)
                ->where('q', $data['q'])
                ->where('r', $data['r'])
                ->update(['is_explored' => 1]);
        }

        if ($building->is_instanced && ! $isHarvester) {
            $nextInstanceId = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', $data['building_id'])
                ->max('instance_id') + 1;
            DB::table('colony_buildings')->insert([
                'colony_id' => $colony->id,
                'building_id' => $data['building_id'],
                'instance_id' => $nextInstanceId,
                'level' => 0,
                'status_points' => $building->max_status_points ?? 20,
                'ap_spend' => 1,
                'tile_x' => $data['q'],
                'tile_y' => $data['r'],
                'placed_at_tick' => $this->getTick(),
            ]);
        } else {
            $nextInstanceId = 1;
            $existing = DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', $data['building_id'])
                ->first();

            if ($existingBuilding) {
                $update = ['tile_x' => $data['q'], 'tile_y' => $data['r']];
                // Preserve pre-invested ap_spend (seeded buildings); reset only on fresh placements.
                if ($existingBuilding->tile_x === null) {
                    $update['ap_spend'] = max((int) $existingBuilding->ap_spend, 1);
                    $update['placed_at_tick'] = $this->getTick();
                } elseif (! $isHarvesterMove) {
                    $update['ap_spend'] = 1;
                }
                // Harvester move: tile updates, ap_spend unchanged.
                // Relocation takes 1 Sol — no production until arrival.
                if ($isHarvesterMove) {
                    $update['pending_until_tick'] = $this->getTick();
                }
                DB::table('colony_buildings')
                    ->where('colony_id', $colony->id)
                    ->where('building_id', $data['building_id'])
                    ->update($update);
                $nextInstanceId = (int) $existingBuilding->instance_id;
            } else {
                DB::table('colony_buildings')->insert([
                    'colony_id' => $colony->id,
                    'building_id' => $data['building_id'],
                    'instance_id' => 1,
                    'level' => 0,
                    'status_points' => $building->max_status_points ?? 20,
                    'ap_spend' => 1,
                    'tile_x' => $data['q'],
                    'tile_y' => $data['r'],
                    'placed_at_tick' => $this->getTick(),
                ]);
            }
        }

        if (! config('game.bypass.ap_checks')) {
            $this->personellService->lockActionPoints('construction', $colony->id, $apCost);
        }

        // Deduct erect cost (Regolith + any Werkstoffe). Harvester relocation is free.
        if (! $isHarvester && ! config('game.bypass.resource_costs') && $buildCost !== []) {
            $costs = [];
            foreach ($buildCost as $resourceId => $amount) {
                $costs[] = ['resource_id' => $resourceId, 'amount' => $amount];
            }
            $this->resourcesService->payCosts($costs, $colony->id);
        }

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $this->getTick(),
            'event' => 'colony.building_placed',
            'area' => 'colony',
            'parameters' => json_encode(['colony_id' => $colony->id, 'building_id' => $data['building_id']]),
        ]);

        $row = $this->fetchBuildingRow($colony->id, $data['building_id'], $nextInstanceId);

        // Harvester relocation: append onboarding tip flag once per user.
        if ((int) $data['building_id'] === BuildingId::Harvester->value) {
            $showTip = ! $this->onboardingTriggerService->hasFired(Auth::id(), 'harvester_move_shown');
            $this->onboardingTriggerService->markFired(Auth::id(), 'harvester_move_shown');

            return response()->json([
                'ok' => true,
                'building' => $row,
                'showHarvesterMoveTip' => $showTip,
                ...$this->currentAp($colony->id),
                'activeHint' => $this->resolveHint($colony->id),
            ]);
        }

        return response()->json(['ok' => true, 'building' => $row, ...$this->currentAp($colony->id), 'activeHint' => $this->resolveHint($colony->id)]);
    }

    public function investBuilding(Request $request): JsonResponse
    {
        $data = $request->validate([
            'building_id' => 'required|integer',
            'instance_id' => 'sometimes|integer',
        ]);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $buildingId = (int) $data['building_id'];
        $instanceId = (int) ($data['instance_id'] ?? 1);

        if (! config('game.bypass.ap_checks') && $this->personellService->getConstructionPoints($colony->id) < 1) {
            return response()->json([
                'ok' => false,
                'error' => 'ap_limit',
                'ap_type' => 'construction',
                'current' => 0,
                'message' => __('colony.onboarding_trigger_ap_limit'),
            ]);
        }

        $row = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->first();

        if (! $row) {
            return response()->json(['ok' => false, 'error' => __('colony.error_building_not_found')]);
        }

        $building = DB::table('buildings')->where('id', $buildingId)->first();

        if ($building->max_level !== null && $row->level >= (int) $building->max_level) {
            return response()->json(['ok' => false, 'error' => __('colony.error_max_level_reached')]);
        }

        // Level-up Regolith is charged only on the click that completes the level (flat,
        // no escalation; CC scales by target level). Check it BEFORE spending the AP so a
        // shortfall never burns the final Construction-AP — the player tops up first.
        $willLevelUp = ($row->ap_spend + 1) >= (int) $building->ap_for_levelup;
        $levelupRegolith = $willLevelUp
            ? $this->levelupRegolithFor($buildingId, (int) $row->level + 1)
            : 0;

        if ($willLevelUp && $levelupRegolith > 0 && ! config('game.bypass.resource_costs')
            && ! $this->resourcesService->check([['resource_id' => self::RES_REGOLITH, 'amount' => $levelupRegolith]], $colony->id)) {
            return response()->json([
                'ok' => false,
                'error' => 'resource_limit',
                'message' => __('colony.error_insufficient_resources'),
                'cost' => [self::RES_REGOLITH => $levelupRegolith],
            ]);
        }

        $newApSpend = min($row->ap_spend + 1, $building->ap_for_levelup);

        DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->update(['ap_spend' => $newApSpend]);

        if (! config('game.bypass.ap_checks')) {
            $this->personellService->lockActionPoints('construction', $colony->id, 1);
        }

        $leveledUp = false;
        if ($newApSpend >= $building->ap_for_levelup) {
            if ($levelupRegolith > 0 && ! config('game.bypass.resource_costs')) {
                $this->resourcesService->payCosts(
                    [['resource_id' => self::RES_REGOLITH, 'amount' => $levelupRegolith]],
                    $colony->id
                );
            }
            DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', $buildingId)
                ->where('instance_id', $instanceId)
                ->update([
                    'level' => $row->level + 1,
                    'ap_spend' => 0,
                    'status_points' => $building->max_status_points ?? 20,
                ]);
            $leveledUp = true;
        }

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $this->getTick(),
            'event' => 'colony.building_invested',
            'area' => 'colony',
            'parameters' => json_encode([
                'building_id' => $buildingId,
                'building_name' => $building->name ?? '',
                'ap_spend' => $newApSpend,
                'ap_for_levelup' => (int) $building->ap_for_levelup,
                'level_up' => $leveledUp,
                'new_level' => $leveledUp ? $row->level + 1 : $row->level,
            ]),
        ]);

        // CC level-up: recalculate colony zone and include updated tiles in response
        if ($leveledUp && $buildingId === BuildingId::CommandCenter->value) {
            $newCcLevel = $row->level + 1;
            $this->tileService->assignColonyZone($colony->id, $newCcLevel);
            $nextZoneKeys = $this->tileService->nextZoneTileKeys($colony->id, $newCcLevel);
            $tiles = $this->tileService->getTilesForColony($colony->id)
                ->map(function ($tile) use ($nextZoneKeys) {
                    $tile['next_zone'] = isset($nextZoneKeys[$tile['q'].','.$tile['r']]);

                    return $tile;
                })
                ->values()
                ->toArray();

            return response()->json([
                'ok' => true,
                'building' => $this->fetchBuildingRow($colony->id, $buildingId, $instanceId),
                'leveled_up' => true,
                'tiles' => $tiles,
                'activeHint' => $this->resolveHint($colony->id),
                'phase_progress' => $this->computePhaseProgress($colony),
                ...$this->currentAp($colony->id),
            ]);
        }

        // Nav-gated buildings (sciencelab=31, hangar=44, bar=52): reaching level 1 unlocks
        // a nav link that was rendered server-side as locked. Signal the client to reload
        // so the nav reflects the new state without manual page refresh.
        $navUnlocked = $leveledUp
            && $row->level === 0
            && in_array($buildingId, [31, 44, 52], true);

        return response()->json([
            'ok' => true,
            'building' => $this->fetchBuildingRow($colony->id, $buildingId, $instanceId),
            'leveled_up' => $leveledUp,
            'nav_unlocked' => $navUnlocked,
            'activeHint' => $this->resolveHint($colony->id),
            ...($leveledUp ? ['phase_progress' => $this->computePhaseProgress($colony)] : []),
            ...$this->currentAp($colony->id),
        ]);
    }

    public function repairBuilding(Request $request): JsonResponse
    {
        $data = $request->validate([
            'building_id' => 'required|integer',
            'instance_id' => 'sometimes|integer',
        ]);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $buildingId = (int) $data['building_id'];
        $instanceId = (int) ($data['instance_id'] ?? 1);

        if (! config('game.bypass.ap_checks') && $this->personellService->getConstructionPoints($colony->id) < 1) {
            return response()->json([
                'ok' => false,
                'error' => 'ap_limit',
                'ap_type' => 'construction',
                'current' => 0,
                'message' => __('colony.onboarding_trigger_ap_limit'),
            ]);
        }

        $row = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->first();

        if (! $row) {
            return response()->json(['ok' => false, 'error' => __('colony.error_building_not_found')]);
        }

        if ((int) $row->level < 1) {
            return response()->json(['ok' => false, 'error' => __('colony.error_repair_under_construction')]);
        }

        $building = DB::table('buildings')->where('id', $buildingId)->first();
        $maxSp = (int) ($building->max_status_points ?? 20);

        if ((int) $row->status_points >= $maxSp) {
            return response()->json(['ok' => false, 'error' => __('colony.error_repair_full')]);
        }

        // Repair costs 2 Regolith per click (hard gate, no negative balance). CC and
        // Harvester are exempt (AP-only) so the Regolith source itself stays repairable —
        // this keeps the decay spiral a recoverable setback, never a hard deadlock.
        $repairRegolith = config('game.repair.regolith_per_click', 2);
        $repairCostsRegolith = $buildingId !== BuildingId::CommandCenter->value
            && $buildingId !== BuildingId::Harvester->value
            && $repairRegolith > 0
            && ! config('game.bypass.resource_costs');

        if ($repairCostsRegolith
            && ! $this->resourcesService->check([['resource_id' => self::RES_REGOLITH, 'amount' => $repairRegolith]], $colony->id)) {
            return response()->json([
                'ok' => false,
                'error' => 'repair_no_regolith',
                'message' => __('colony.error_repair_no_regolith'),
            ]);
        }

        $newSp = min((int) $row->status_points + 1, $maxSp);

        DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->update(['status_points' => $newSp]);

        if ($repairCostsRegolith) {
            $this->resourcesService->payCosts(
                [['resource_id' => self::RES_REGOLITH, 'amount' => $repairRegolith]],
                $colony->id
            );
        }

        if (! config('game.bypass.ap_checks')) {
            $this->personellService->lockActionPoints('construction', $colony->id, 1);
        }

        // Repair is a teaching hint, not a chore: dismiss it after the first repair
        // click so it does not nag while buildings are still (intentionally) below max.
        // The player has learned the action; topping up the rest is optional unless a
        // building is leveldown-threatened (handled separately).
        $this->hintService->dismissHint(Auth::id(), 'hint_repair');

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $this->getTick(),
            'event' => 'colony.building_repaired',
            'area' => 'colony',
            'parameters' => json_encode([
                'building_id' => $buildingId,
                'building_name' => $building->name ?? '',
                'status_points' => $newSp,
                'max_status_points' => $maxSp,
            ]),
        ]);

        return response()->json([
            'ok' => true,
            'building' => $this->fetchBuildingRow($colony->id, $buildingId, $instanceId),
            'activeHint' => $this->resolveHint($colony->id),
            ...$this->currentAp($colony->id),
        ]);
    }

    /**
     * Nexus direct import of Werkstoffe (compounds) against Credits.
     *
     * Guaranteed safety-net source (GDD §3): always available, fixed Credits price,
     * gated behind Uplink-Station Lv1 (an "active Nexus request"). Pricier than the
     * opportunistic Cantina/merchant — those stay the cheaper, random source.
     */
    public function nexusImportCompounds(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1|max:9999',
        ]);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $amount = (int) $data['amount'];

        $uplinkId = (int) config('buildings.uplinkStation.id', 54);
        $uplinkLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $uplinkId)
            ->value('level') ?? 0);

        if ($uplinkLevel < 1) {
            return response()->json(['ok' => false, 'error' => 'uplink_required', 'message' => __('colony.nexus_import_uplink_required')]);
        }

        $price = (int) config('game.economy.compound_import_price', 90);
        $totalCost = $amount * $price;

        if (! $this->resourcesService->check([['resource_id' => ResourcesService::RES_CREDITS, 'amount' => $totalCost]], $colony->id)) {
            return response()->json(['ok' => false, 'error' => 'credit_limit', 'message' => __('colony.nexus_import_no_credits')]);
        }

        $this->resourcesService->payCosts([['resource_id' => ResourcesService::RES_CREDITS, 'amount' => $totalCost]], $colony->id);
        $this->resourcesService->increaseAmount($colony->id, 4, $amount);   // 4 = Werkstoffe

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $this->getTick(),
            'event' => 'colony.compounds_imported',
            'area' => 'colony',
            'parameters' => json_encode(['colony_id' => $colony->id, 'amount' => $amount, 'cost' => $totalCost]),
        ]);

        $credits = (int) (DB::table('user_resources')->where('user_id', $colony->user_id)->value('credits') ?? 0);
        $compounds = (int) (DB::table('colony_resources')->where('colony_id', $colony->id)->where('resource_id', 4)->value('amount') ?? 0);

        return response()->json([
            'ok' => true,
            'amount' => $amount,
            'cost' => $totalCost,
            'credits' => $credits,
            'compounds' => $compounds,
        ]);
    }

    public function dismissHint(Request $request): JsonResponse
    {
        $data = $request->validate(['hint_key' => 'required|string|max:20']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $this->hintService->dismissHint(Auth::id(), $data['hint_key']);

        return response()->json(['ok' => true, 'hint' => $this->resolveHint($colony->id)]);
    }

    public function rename(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[^<>{}\[\]]*$/'],
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        DB::table('glx_colonies')
            ->where('id', $colony->id)
            ->update(['name' => $request->input('name')]);

        return redirect()->route('lobby')
            ->with('success', 'Kolonienname wurde aktualisiert.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveHint(int $colonyId): ?array
    {
        $hint = $this->hintService->getActiveHint($colonyId, Auth::id());
        if ($hint) {
            $hint['text'] = __($hint['text_key']);
        }

        return $hint;
    }

    private function currentAp(int $colonyId): array
    {
        return [
            'apNav' => $this->personellService->getAvailableActionPoints('navigation', $colonyId),
            'apConstruction' => $this->personellService->getAvailableActionPoints('construction', $colonyId),
            // Build-chip affordability check (greys out unaffordable buildings) needs
            // these alongside AP — kept on the same payload so every action that
            // refreshes AP also refreshes resources.
            'regolith' => (int) (DB::table('colony_resources')->where('colony_id', $colonyId)->where('resource_id', 3)->value('amount') ?? 0),
            'werkstoffe' => (int) (DB::table('colony_resources')->where('colony_id', $colonyId)->where('resource_id', 4)->value('amount') ?? 0),
            'freeSupply' => $this->resourcesService->getFreeSupply($colonyId),
        ];
    }

    private function hexDistance(int $q1, int $r1, int $q2, int $r2): int
    {
        $dq = $q2 - $q1;
        $dr = $r2 - $r1;

        return (abs($dq) + abs($dr) + abs($dq + $dr)) / 2;
    }

    private function fetchBuildingRow(int $colonyId, int $buildingId, int $instanceId = 1): object
    {
        $row = DB::table('colony_buildings')
            ->join('buildings', 'colony_buildings.building_id', '=', 'buildings.id')
            ->where('colony_buildings.colony_id', $colonyId)
            ->where('colony_buildings.building_id', $buildingId)
            ->where('colony_buildings.instance_id', $instanceId)
            ->select(
                'colony_buildings.building_id',
                'colony_buildings.instance_id',
                'colony_buildings.level',
                'colony_buildings.status_points',
                'colony_buildings.ap_spend',
                'colony_buildings.tile_x',
                'colony_buildings.tile_y',
                'colony_buildings.pending_until_tick',
                'buildings.name as building_key',
                'buildings.max_level',
                'buildings.ap_for_levelup',
                'buildings.max_status_points',
            )
            ->first();

        $row->label = __('techtree.'.$row->building_key);
        $row->image_slug = self::buildingImageSlug($row->building_key);
        $row->in_transit = $row->pending_until_tick !== null && (int) $row->pending_until_tick >= $this->getTick();
        $row->levelup_cost = $this->levelupRegolithFor((int) $row->building_id, (int) $row->level + 1);

        return $row;
    }

    private static function buildingImageSlug(string $key): string
    {
        $key = preg_replace('/^building_/', '', $key);
        $overrides = ['bar' => 'cantina'];

        return $overrides[$key] ?? strtolower(preg_replace('/([A-Z])/', '-$1', $key));
    }

    private function computePhaseProgress(Colony $colony): array
    {
        $run = DB::table('runs')
            ->where('colony_id', $colony->id)
            ->where('status', 'active')
            ->select('id', 'phase', 'current_tick')
            ->first();

        if (! $run) {
            return ['phase' => 1, 'criteria' => []];
        }

        $colonyId = $colony->id;
        $ccId = config('buildings.commandCenter.id', 25);

        if ((int) $run->phase === 1) {
            $ccLevel = (int) (DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', $ccId)
                ->value('level') ?? 0);

            $buildingsLv2 = DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', '!=', $ccId)
                ->where('level', '>=', 2)
                ->count();

            $advisorCount = Advisor::where('colony_id', $colonyId)
                ->where(function ($q) use ($run): void {
                    $q->whereNull('unavailable_until_tick')
                        ->orWhere('unavailable_until_tick', '<', $run->current_tick);
                })
                ->count();

            return [
                'phase' => 1,
                'criteria' => [
                    [
                        'key' => 'cc_level',
                        'label' => __('colony.sol_report_phase1_cc'),
                        'current' => min($ccLevel, 3),
                        'target' => 3,
                        'done' => $ccLevel >= 3,
                    ],
                    [
                        'key' => 'buildings_lv2',
                        'label' => __('colony.sol_report_phase1_buildings'),
                        'current' => min($buildingsLv2, 2),
                        'target' => 2,
                        'done' => $buildingsLv2 >= 2,
                    ],
                    [
                        'key' => 'advisors',
                        'label' => __('colony.sol_report_phase1_advisors'),
                        'current' => min($advisorCount, 3),
                        'target' => 3,
                        'done' => $advisorCount >= 3,
                    ],
                ],
            ];
        }

        $objectives = DB::table('run_objectives')
            ->where('run_id', $run->id)
            ->orderBy('id')
            ->get(['task_key', 'current_value', 'target_value', 'completed_at'])
            ->map(function ($obj): array {
                $revealed = (int) $obj->current_value > 0 || $obj->completed_at !== null;

                return [
                    'revealed' => $revealed,
                    'label' => $revealed ? __('run.'.$obj->task_key) : null,
                    'current' => (int) $obj->current_value,
                    'target' => (int) $obj->target_value,
                    'done' => $obj->completed_at !== null,
                ];
            })
            ->values()
            ->all();

        return ['phase' => 2, 'objectives' => $objectives];
    }
}
