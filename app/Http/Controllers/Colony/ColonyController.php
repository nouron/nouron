<?php

namespace App\Http\Controllers\Colony;

use App\Enums\BuildingId;
use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\ColonyTileService;
use App\Services\EventService;
use App\Services\HarvesterEntitlementService;
use App\Services\MerchantService;
use App\Services\OnboardingHintService;
use App\Services\OnboardingTriggerService;
use App\Services\ResourcesService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use App\Services\TrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        private readonly TrustService $trustService,
        private readonly HarvesterEntitlementService $harvesterEntitlementService,
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

    /** Flat Regolith cost for a level-up on any non-CC, non-Harvester building (GDD §13.7). */
    private const LEVELUP_REGOLITH_FLAT = 25;

    /**
     * Regolith consumed when a building completes a level-up.
     * Rules: CommandCenter scales as target_level × cc_upgrade_regolith_per_level;
     * Harvester is free (bootstrap); all others pay a flat rate, independent of build_cost.
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

        return self::LEVELUP_REGOLITH_FLAT;
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
            ->value('level');

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

        $phaseProgress = $this->colonyService->getPhaseProgress($colony);

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

        return response()->json([...$result, ...$extra], $result['ok'] ? 200 : 422);
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

        return response()->json([...$result, ...$extra], $result['ok'] ? 200 : 422);
    }

    // ── Building actions ──────────────────────────────────────────────────────

    public function availableBuildings(): JsonResponse
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', BuildingId::CommandCenter->value)->value('level');

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
            ->select('id', 'name', 'ap_for_levelup', 'max_status_points', 'max_level', 'max_instances',
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
                    if ($count >= ($b->max_instances ?? PHP_INT_MAX)) {
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
            // Harvester only: 1 (default, bootstrap-exempt) or 2 (paid expansion, GDD §4c).
            'instance_id' => 'sometimes|integer|in:1,2',
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        $tile = DB::table('colony_tiles')
            ->where('colony_id', $colony->id)
            ->where('q', $data['q'])
            ->where('r', $data['r'])
            ->first();

        if (! $tile) {
            return $this->fail('tile_not_found');
        }
        $isHarvester = (int) $data['building_id'] === BuildingId::Harvester->value;

        if ($isHarvester) {
            // Harvester relocates to an explored regolith tile in the exploration zone (ring 3+).
            if (! $tile->is_explored) {
                return $this->fail('not_explored');
            }
            if (! str_starts_with($tile->tile_type, 'regolith_')) {
                return $this->fail('harvester_needs_regolith');
            }
        } else {
            // Regular buildings need only colony-zone permission. The zone is no longer
            // auto-explored (see ColonyTileService::assignColonyZone) — building on a
            // still-fogged zone tile is allowed and reveals it ("settle → see").
            if (! $tile->is_colony_zone) {
                return $this->fail('tile_outside_colony');
            }
            if (! str_starts_with($tile->tile_type, 'terrain_') || $tile->tile_type === 'terrain_impassable') {
                return $this->fail('tile_not_buildable');
            }
        }

        $occupied = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('tile_x', $data['q'])
            ->where('tile_y', $data['r'])
            ->exists();
        if ($occupied) {
            return $this->fail('tile_occupied');
        }

        $building = DB::table('buildings')->where('id', $data['building_id'])->first();
        if (! $building) {
            return $this->fail('building_not_found');
        }

        // Harvester instance targeted by this request — 1 (default, bootstrap-exempt,
        // always moved/UPDATEd, never duplicated) or 2 (paid expansion, GDD §4c). Every
        // other building keeps the existing single-row-per-instanced-slot lookup.
        $requestedInstanceId = $isHarvester ? (int) ($data['instance_id'] ?? 1) : 1;

        $existingBuilding = ($isHarvester || ! $building->is_instanced)
            ? DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', $data['building_id'])
                ->where('instance_id', $requestedInstanceId)
                ->first()
            : null;

        $isHarvesterMove = $isHarvester
            && $existingBuilding !== null
            && $existingBuilding->tile_x !== null;

        if ($isHarvesterMove
                && $existingBuilding->pending_until_tick !== null
                && (int) $existingBuilding->pending_until_tick >= $this->getTick()) {
            return $this->fail('harvester_in_transit');
        }

        // Second Harvester instance gate (GDD §4c "Harvester-Zweitinstanz:
        // Bezugsquelle", freigegeben 2026-08-05): instance 1 keeps the Regolith-free
        // bootstrap exemption. Instance 2 keeps the CommandCenter-level gate, but is no
        // longer a deterministic Regolith buy — it requires an opportunistic entitlement
        // earned via Weg A (Orin's purchase, CorporateContactService) or Weg B
        // (mission_harvester_salvage reward), see HarvesterEntitlementService. Only
        // applies to the FRESH placement (not a subsequent relocation of an
        // already-placed instance 2).
        $isSecondInstanceFreshPlacement = $isHarvester && $requestedInstanceId === 2 && ! $isHarvesterMove;
        $secondInstanceIsSalvageSourced = false;

        if ($isSecondInstanceFreshPlacement) {
            $requiredCcLevel = (int) config('game.harvester.second_instance_cc_level', 3);
            $ccLevel = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', BuildingId::CommandCenter->value)
                ->value('level');

            if ($ccLevel < $requiredCcLevel) {
                return $this->fail('harvester_second_instance_cc_gate');
            }

            if (! $this->harvesterEntitlementService->hasEntitlement(Auth::id())) {
                return $this->fail('harvester_second_instance_locked');
            }

            $secondInstanceIsSalvageSourced = $this->harvesterEntitlementService->isSalvageSourced(Auth::id());
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
                return $this->fail('agrardom_required');
            }
        }

        // Verlegekosten 1 → 2 AP je Hex (GDD §4c, freigegeben 2026-08-03) — the
        // relocation-frequency lever, not the depletion curve (config('game.harvester.relocate_ap_per_hex')).
        $apCost = $isHarvesterMove
            ? max(1, $this->hexDistance((int) $existingBuilding->tile_x, (int) $existingBuilding->tile_y, (int) $data['q'], (int) $data['r'])
                * (int) config('game.harvester.relocate_ap_per_hex', 2))
            : 1;

        if (! config('game.bypass.ap_checks') && $this->personellService->getConstructionPoints($colony->id) < $apCost) {
            return $this->fail('ap_limit', __('colony.onboarding_trigger_ap_limit'), [
                'ap_type' => 'construction',
                'current' => $this->personellService->getConstructionPoints($colony->id),
            ]);
        }

        // Resource + supply gate. Harvester relocation (and both instances — the first
        // stays the bootstrap exemption, the second is now paid in Credits via Orin or
        // in reparation effort via the salvage mission, not in Regolith at placement
        // time, GDD §4c 2026-08-05) is free — CC/Harvester carry no build_cost. Checked
        // before any DB write so a failed gate leaves the colony untouched.
        $buildCost = $isHarvester ? [] : $this->buildCostFor((int) $data['building_id']);
        $chargesBuildCost = ! $isHarvester;

        if ($chargesBuildCost) {
            if (! config('game.bypass.resource_costs') && $buildCost !== []) {
                $costs = [];
                foreach ($buildCost as $resourceId => $amount) {
                    $costs[] = ['resource_id' => $resourceId, 'amount' => $amount];
                }
                if (! $this->resourcesService->check($costs, $colony->id)) {
                    return $this->fail('resource_limit', __('colony.error_insufficient_resources'), ['cost' => $buildCost]);
                }
            }

            // Supply is a cap, not a stockpile: a building may only be erected when the
            // free cap covers its ongoing supply_cost (§6). Nothing is deducted here.
            if (! config('game.bypass.supply_checks')
                && (int) ($building->supply_cost ?? 0) > 0
                && $this->resourcesService->getFreeSupply($colony->id) < (int) $building->supply_cost) {
                return $this->fail('supply_limit', __('colony.onboarding_trigger_supply_full'));
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
                $maxSp = (int) ($building->max_status_points ?? 20);
                // Salvage-sourced second Harvester instance (Weg B, GDD §4c 2026-08-05):
                // arrives damaged, not fully productive — the "cheaper but not free" trade
                // against Orin's Credits price (Weg A, always full health).
                $initialStatusPoints = $secondInstanceIsSalvageSourced
                    ? (int) round($maxSp * (float) config('game.harvester.salvage_arrival_sp_pct', 0.25))
                    : $maxSp;

                DB::table('colony_buildings')->insert([
                    'colony_id' => $colony->id,
                    'building_id' => $data['building_id'],
                    'instance_id' => $requestedInstanceId,
                    'level' => 0,
                    'status_points' => $initialStatusPoints,
                    'ap_spend' => 1,
                    'tile_x' => $data['q'],
                    'tile_y' => $data['r'],
                    'placed_at_tick' => $this->getTick(),
                ]);
                $nextInstanceId = $requestedInstanceId;
            }
        }

        if (! config('game.bypass.ap_checks')) {
            $this->personellService->lockActionPoints('construction', $colony->id, $apCost);
        }

        // Deduct erect cost (Regolith + any Werkstoffe). Harvester relocation (and its
        // bootstrap instance) is free — the second instance's flat Regolith cost is not.
        if ($chargesBuildCost && ! config('game.bypass.resource_costs') && $buildCost !== []) {
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
            return $this->fail('ap_limit', __('colony.onboarding_trigger_ap_limit'), [
                'ap_type' => 'construction',
                'current' => 0,
            ]);
        }

        $row = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->first();

        if (! $row) {
            return $this->fail('building_not_found');
        }

        $building = DB::table('buildings')->where('id', $buildingId)->first();

        if ($building->max_level !== null && $row->level >= (int) $building->max_level) {
            return $this->fail('max_level_reached');
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
            return $this->fail('resource_limit', __('colony.error_insufficient_resources'), [
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
                'phase_progress' => $this->colonyService->getPhaseProgress($colony),
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
            ...($leveledUp ? ['phase_progress' => $this->colonyService->getPhaseProgress($colony)] : []),
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
            return $this->fail('ap_limit', __('colony.onboarding_trigger_ap_limit'), [
                'ap_type' => 'construction',
                'current' => 0,
            ]);
        }

        $row = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->first();

        if (! $row) {
            return $this->fail('building_not_found');
        }

        if ((int) $row->level < 1) {
            return $this->fail('repair_under_construction');
        }

        $building = DB::table('buildings')->where('id', $buildingId)->first();
        $maxSp = (int) ($building->max_status_points ?? 20);

        if ((int) $row->status_points >= $maxSp) {
            return $this->fail('repair_full');
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
            return $this->fail('repair_no_regolith');
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
            return $this->fail('uplink_required', __('colony.nexus_import_uplink_required'));
        }

        $price = (int) config('game.economy.compound_import_price', 90);
        $totalCost = $amount * $price;

        if (! $this->resourcesService->check([['resource_id' => ResourcesService::RES_CREDITS, 'amount' => $totalCost]], $colony->id)) {
            return $this->fail('credit_limit', __('colony.nexus_import_no_credits'));
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

    /**
     * Kolonisten-Zulage (GDD §14) — spend Credits to fire a one-shot Trust
     * event. Max one tier per colony per Sol (different stipend event_keys
     * don't dedupe against each other in TrustService's same-key collapse).
     */
    public function purchaseStipend(Request $request): JsonResponse
    {
        $tiers = config('game.stipend.tiers', []);

        $data = $request->validate([
            'tier' => ['required', 'string', Rule::in(array_keys($tiers))],
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $tierCfg = $tiers[$data['tier']];
        $cost = (int) $tierCfg['cost'];
        $eventKey = (string) $tierCfg['event_key'];
        $allStipendKeys = array_column($tiers, 'event_key');

        // fireEvent()'s default (tick+1) is what the *next* GameTick run reads
        // (TrustService::eventContribution matches tick exactly) — computed
        // once so the guard and the insert agree on the same target tick.
        $targetTick = $this->getTick() + 1;

        if ($this->trustService->hasEventThisTick($colony->id, $targetTick, $allStipendKeys)) {
            return $this->fail('stipend_already_used', __('colony.stipend_already_used'));
        }

        if (! $this->resourcesService->check([['resource_id' => ResourcesService::RES_CREDITS, 'amount' => $cost]], $colony->id)) {
            return $this->fail('stipend_no_credits', __('colony.stipend_no_credits'));
        }

        $this->resourcesService->payCosts([['resource_id' => ResourcesService::RES_CREDITS, 'amount' => $cost]], $colony->id);
        $this->trustService->fireEvent($colony->id, $eventKey, $targetTick);

        // Logged at $targetTick (not the current tick) so it surfaces in the
        // Sol-Report of the Sol it actually takes effect on — SolReportService
        // reads colony_log at the just-processed (post-increment) tick, which
        // is exactly $targetTick once "Sol beenden" is clicked.
        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $targetTick,
            'event' => 'colony.stipend_purchased',
            'area' => 'colony',
            'parameters' => json_encode(['colony_id' => $colony->id, 'tier' => $data['tier'], 'cost' => $cost]),
        ]);

        $credits = (int) (DB::table('user_resources')->where('user_id', $colony->user_id)->value('credits') ?? 0);

        return response()->json([
            'ok' => true,
            'tier' => $data['tier'],
            'cost' => $cost,
            'credits' => $credits,
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

    /**
     * Reject an action the game rules do not allow, as 422.
     *
     * `error` is always a stable machine code and `message` always the text for the
     * player — never the other way round. Callers used to put the translated string
     * straight into `error`, which made the field unusable as a key: anything counting
     * or branching on it broke the moment a translation changed.
     *
     * 422 matches HangarController/BarController/AdvisorController. The colony endpoints
     * used to answer 200 for rule violations, so a client had to read the body to notice
     * a failure at all.
     *
     * @param  string|null  $message  defaults to the `colony.error_<code>` line
     * @param  array<string, mixed>  $extra  extra context for the client (ap_type, cost, …)
     */
    private function fail(string $code, ?string $message = null, array $extra = []): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error' => $code,
            'message' => $message ?? __("colony.error_{$code}"),
            ...$extra,
        ], 422);
    }

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
                'buildings.max_instances',
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
}
