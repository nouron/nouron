<?php

namespace App\Http\Controllers\Colony;

use App\Enums\BuildingId;
use App\Http\Controllers\BaseController;
use App\Services\AdvisorService;
use App\Services\BuildingCostService;
use App\Services\CharacterCodexService;
use App\Services\ColonyService;
use App\Services\ColonyTileService;
use App\Services\EventService;
use App\Services\HarvesterEntitlementService;
use App\Services\MerchantService;
use App\Services\NexusImportService;
use App\Services\OnboardingHintService;
use App\Services\OnboardingTriggerService;
use App\Services\OvercapService;
use App\Services\ProjectBonusService;
use App\Services\ResourcesService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\BuildingUnlockService;
use App\Services\TickService;
use App\Services\TrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        private readonly AdvisorService $advisorService,
        private readonly OnboardingHintService $hintService,
        private readonly OnboardingTriggerService $onboardingTriggerService,
        private readonly MerchantService $merchantService,
        private readonly EventService $eventService,
        private readonly ResourcesService $resourcesService,
        private readonly TrustService $trustService,
        private readonly HarvesterEntitlementService $harvesterEntitlementService,
        private readonly ProjectBonusService $projectBonusService,
        private readonly BuildingUnlockService $buildingUnlockService,
        private readonly CharacterCodexService $characterCodexService,
        private readonly NexusImportService $nexusImportService,
        private readonly OvercapService $overcapService,
        private readonly BuildingService $buildingService,
        private readonly BuildingCostService $buildingCostService,
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

    /** Whether an Agrardom (bioFacility) has been placed in the given colony. */
    private function agrardomPlaced(int $colonyId): bool
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', (int) config('buildings.bioFacility.id', 41))
            ->whereNotNull('tile_x')
            ->exists();
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

        // Same activity gate GameTick::generateHarvesterYield() uses to decide whether
        // a Harvester instance produces this Sol (Owner-Playtest-Fund 2026-09-04: the
        // depleting resource_amount was invisible to the player, a shrinking yield
        // looked like a bug instead of the intended depletion mechanic). Shared with
        // OnboardingHintService — see ColonyTileService::activeHarvesterRegolithTiles().
        $activeRegolithTiles = $this->tileService->activeHarvesterRegolithTiles($colony->id, $globalTick)
            ->keyBy(fn ($tile) => $tile->q.','.$tile->r);

        // A25 (docs/superpowers/specs/2026-08-10-harvester-constant-yield-design.md
        // §2): "≈N Sole bis Erschöpfung" estimate needs the same geology/trust
        // inputs GameTick::generateHarvesterYield() uses — computed once here,
        // not per tile.
        $geologyLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colony->id)
            ->where('research_id', (int) config('knowledge.geology.id', 92))
            ->value('level');
        $trustMultiplier = $this->trustService->getProductionMultiplier($this->trustService->getTrust($colony->id));

        $tiles = $tiles->map(function ($tile) use ($activeRegolithTiles, $geologyLevel, $trustMultiplier) {
            $active = $activeRegolithTiles->get($tile['q'].','.$tile['r']);
            if ($active !== null) {
                $tile['regolith_remaining'] = $active->resource_amount;
                $tile['regolith_max'] = $active->resource_max;
                $tile['sols_remaining'] = $this->tileService->solsRemaining(
                    $active->tile_type,
                    $active->resource_amount,
                    $active->resource_max,
                    $geologyLevel,
                    $trustMultiplier
                );
            }

            return $tile;
        });

        // Ausweich-Tiles (GDD §4c BALANCE CONCERN, Owner-Playtest-Fund 2026-09-04):
        // explored, not-yet-depleted regolith tiles other than the currently active
        // Harvester tile(s) — the pre-scouted Ring-3 relocation targets the map should
        // highlight once the active tile runs low. Pure data for the map UI (see
        // OnboardingHintService::checkHintHarvesterLowRegolith() for the accompanying
        // hint bar warning).
        $activeRegolithKeys = $activeRegolithTiles->keys();
        $regolithFallbackTiles = DB::table('colony_tiles')
            ->where('colony_id', $colony->id)
            ->where('is_explored', 1)
            ->where('resource_max', '>', 0)
            ->where('resource_amount', '>', 0)
            ->get(['q', 'r'])
            ->reject(fn ($tile) => $activeRegolithKeys->contains($tile->q.','.$tile->r))
            ->map(fn ($tile) => ['q' => (int) $tile->q, 'r' => (int) $tile->r])
            ->values();

        // Name lookup for computeRequiredList() — buildings only ever carry a
        // single required_building_id (no required_building2_id, unlike researches).
        $buildingNames = DB::table('buildings')->pluck('name', 'id');

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
                'buildings.required_building_id',
                'buildings.required_building_level',
            )
            ->get()
            ->map(function ($b) use ($globalTick, $colony, $buildingNames) {
                $b->label = __('techtree.'.$b->building_key);
                $b->image_slug = self::buildingImageSlug($b->building_key);
                $b->description = __('buildings.'.preg_replace('/^building_/', '', $b->building_key).'_desc');
                $b->unlocks_next_level = $this->buildingUnlockService->unlocksAtLevel((int) $b->building_id, (int) $b->level + 1);
                $b->required_list = $this->computeRequiredList(
                    $b->required_building_id !== null ? (int) $b->required_building_id : null,
                    $b->required_building_level !== null ? (int) $b->required_building_level : null,
                    $buildingNames
                );
                $b->in_transit = $b->pending_until_tick !== null && (int) $b->pending_until_tick >= $globalTick;
                // The 0 -> 1 step of a placed site was paid on placement (T9) — 0 then.
                $b->levelup_cost = $this->buildingCostService->levelupRegolithDue($b);
                $b->first_level_prepaid = BuildingCostService::firstLevelPrepaid($b);
                $b->ap_for_levelup = $this->projectBonusService->effectiveApForLevelup($colony->id, (int) $b->ap_for_levelup);
                $b->tier_label = $this->resolveTierLabel((int) $b->building_id, (int) $b->level);

                return $b;
            });

        $colonyAp = $this->advisorService->getAvailableActionPoints($colony->id);
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

        return view('colony.hexview', compact('colony', 'tiles', 'ccLevel', 'buildings', 'colonyAp', 'activeHint', 'supplyCapFull', 'trust', 'regolith', 'werkstoffe', 'freeSupply', 'currentSol', 'solLimit', 'merchantVisit', 'merchantItems', 'phaseProgress', 'regolithFallbackTiles'));
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

        $agrardomPlaced = $this->agrardomPlaced($colony->id);

        $buildings = DB::table('buildings')
            ->select('id', 'name', 'ap_for_levelup', 'max_status_points', 'max_level', 'max_instances',
                'required_building_id', 'required_building_level', 'is_instanced', 'supply_cost')
            ->get()
            ->filter(function ($b) use ($ccLevel, $placedCounts, $agrardomPlaced) {
                // CC already exists; Harvester is placed from a regolith tile.
                if (! BuildingId::isBuildMenuBuilding((int) $b->id)) {
                    return false;
                }
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
                'ap_for_levelup' => $this->projectBonusService->effectiveApForLevelup($colony->id, (int) $b->ap_for_levelup),
                'max_level' => $b->max_level,
                'max_status_points' => $b->max_status_points,
                'is_instanced' => (bool) $b->is_instanced,
                'supply_cost' => (int) $b->supply_cost,
                // Placing pays erect cost + first-level Regolith in one go (T9):
                // build_cost/first_level_regolith are the breakdown, placement_cost
                // the amount actually charged. All [resource_id => amount].
                'build_cost' => $this->buildingCostService->erectCost((int) $b->id),
                'first_level_regolith' => $this->buildingCostService->firstLevelRegolith((int) $b->id),
                'placement_cost' => $this->buildingCostService->placementCost((int) $b->id),
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

        // The CC centre (0,0) is always occupied: the Command Center has no tile row
        // (tile_x = null) and is only drawn there by the client — never trust the
        // client to keep it free. Covers every placement path, Harvester included.
        if ((int) $data['q'] === 0 && (int) $data['r'] === 0) {
            return $this->fail('tile_occupied');
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

            // Purchase wins over salvage when both are somehow present (edge case: both
            // earned before either is consumed by placement) — a paid-for instance must
            // never be downgraded by an also-earned salvage entitlement.
            $secondInstanceIsSalvageSourced = $this->harvesterEntitlementService->isSalvageSourced(Auth::id())
                && ! $this->harvesterEntitlementService->hasPurchaseEntitlement(Auth::id());
        }

        // Agrardom gate: path buildings require Agrardom (41) to be placed first.
        // Agrardom is a hard prerequisite for CC Lv2 — building a path building before
        // Agrardom would leave the player unable to advance.
        if (in_array((int) $data['building_id'], self::PATH_BUILDING_IDS, true) && ! $this->agrardomPlaced($colony->id)) {
            return $this->fail('agrardom_required');
        }

        // Verlegekosten 1 → 2 AP je Hex (GDD §4c, freigegeben 2026-08-03) — the
        // relocation-frequency lever, not the depletion curve (config('game.harvester.relocate_ap_per_hex')).
        $apCost = $isHarvesterMove
            ? max(1, $this->hexDistance((int) $existingBuilding->tile_x, (int) $existingBuilding->tile_y, (int) $data['q'], (int) $data['r'])
                * (int) config('game.harvester.relocate_ap_per_hex', 2))
            : 1;

        if (! config('game.bypass.ap_checks') && $this->advisorService->getAvailableActionPoints($colony->id) < $apCost) {
            return $this->fail('ap_limit', __('colony.onboarding_trigger_ap_limit'), [
                'ap_type' => 'construction',
                'current' => $this->advisorService->getAvailableActionPoints($colony->id),
            ]);
        }

        // Resource + supply gate. Harvester relocation (and both instances — the first
        // stays the bootstrap exemption, the second is now paid in Credits via Orin or
        // in reparation effort via the salvage mission, not in Regolith at placement
        // time, GDD §4c 2026-08-05) is free — CC/Harvester carry no build_cost. Every
        // other placement pays erect cost + the 0 -> 1 level-up Regolith in one go
        // (Owner rule 2026-09-25, T9) — the first invest cycle afterwards is free.
        // Checked before any DB write so a failed gate leaves the colony untouched.
        $buildCost = $isHarvester ? [] : $this->buildingCostService->placementCost((int) $data['building_id']);
        $chargesBuildCost = ! $isHarvester;

        // The first Harvester is the unchecked bootstrap (GDD §6 "Supply als Bau-Gate");
        // the fresh second instance occupies supply like any other building.
        if ($isSecondInstanceFreshPlacement
            && ! config('game.bypass.supply_checks')
            && $this->resourcesService->getFreeSupply($colony->id) < (int) ($building->supply_cost ?? 0)) {
            return $this->fail('supply_limit', __('colony.onboarding_trigger_supply_full'));
        }

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
            // free cap covers its ongoing supply_cost (§6). Nothing is deducted here —
            // from now on the level-0 building reserves the workplaces of its first
            // level (ResourcesService::buildingWorkplaces()), so the later 0 → 1 build
            // can no longer fail on supply.
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
                // Harvester move: tile updates, ap_spend unchanged. Relocation takes
                // 1 Sol — no production until arrival. placed_at_tick also advances
                // here (GDD §9 "Geologische Instabilität": risk is keyed off Sols
                // since the LAST relocation, not just the original placement).
                // instability_outage_until_tick is cleared — GDD §9's stated
                // counter-play for Geologische Instabilität is "Relocation setzt
                // Zähler zurück"; relocating during an active outage ends it.
                if ($isHarvesterMove) {
                    $update['pending_until_tick'] = $this->getTick();
                    $update['placed_at_tick'] = $this->getTick();
                    $update['instability_outage_until_tick'] = null;
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
            $this->advisorService->lockActionPoints($colony->id, $apCost);
        }

        // Deduct the placement cost (erect cost + first-level Regolith, plus any
        // Werkstoffe). Every Harvester placement — relocation, bootstrap instance,
        // entitlement-based second instance — is Regolith-free.
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
            'parameters' => json_encode([
                'colony_id' => $colony->id,
                'building_id' => $data['building_id'],
                'instance_id' => $nextInstanceId,
            ]),
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

        $row = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->first();

        if (! $row) {
            return $this->fail('building_not_found');
        }

        // Agrardom gate (GDD §4 "Agrardom wird Pflichtgebäude vor CC Lv2"): only the
        // Lv1 -> Lv2 jump is gated — later CC levels have no Agrardom requirement.
        // Checked before the AP-limit/resource gates below so a rejected click never
        // locks AP or spends Regolith.
        if ($buildingId === BuildingId::CommandCenter->value
            && (int) $row->level === 1
            && ! $this->agrardomPlaced($colony->id)) {
            return $this->fail('agrardom_required');
        }

        if (! config('game.bypass.ap_checks') && $this->advisorService->getAvailableActionPoints($colony->id) < 1) {
            return $this->fail('ap_limit', __('colony.onboarding_trigger_ap_limit'), [
                'ap_type' => 'construction',
                'current' => 0,
            ]);
        }

        $building = DB::table('buildings')->where('id', $buildingId)->first();

        if ($building->max_level !== null && $row->level >= (int) $building->max_level) {
            return $this->fail('max_level_reached');
        }

        // Supply build gate (GDD §6 "Supply als Bau-Gate", A14): the next level must
        // fit into the free cap (cap − workplaces). Unfilled workplaces after an
        // over-capacity departure keep occupying the cap, so the gate stays closed
        // until everyone is back. Command Center and housing are exempt — they create
        // the housing that leads out of over-capacity. The first level of a placed
        // building is never checked: its workplaces were reserved on placement
        // (ResourcesService::reservesFirstLevel()). Checked before any write.
        if (! ResourcesService::reservesFirstLevel($row)
            && $this->levelUpBlockedBySupply($colony->id, $buildingId, (int) ($building->supply_cost ?? 0))) {
            return $this->fail('supply_limit');
        }

        // Construction knowledge (plus any active discount voucher) discounts the AP
        // threshold (GDD §13.3, docs/superpowers/specs/2026-08-15-knowledge-effects-
        // and-encounters-design.md §2). Level-up Regolith is charged on the click that
        // STARTS the cycle (ap_spend 0 → >0) — mirrors the erect-cost pattern (paid at
        // build start, not completion) so the sidebar's "Kosten bei Baubeginn" copy is
        // accurate. A shortfall blocks the invest entirely, before any AP is spent.
        // The 0 -> 1 cycle of a placed site was already paid on placement (T9) —
        // levelupRegolithDue() returns 0 for it, whatever its ap_spend reads.
        $effectiveApForLevelup = $this->projectBonusService->effectiveApForLevelup($colony->id, (int) $building->ap_for_levelup);
        $isCycleStart = (int) $row->ap_spend === 0;
        $levelupRegolith = $isCycleStart
            ? $this->buildingCostService->levelupRegolithDue($row)
            : 0;

        if ($isCycleStart && $levelupRegolith > 0 && ! config('game.bypass.resource_costs')
            && ! $this->resourcesService->check([['resource_id' => self::RES_REGOLITH, 'amount' => $levelupRegolith]], $colony->id)) {
            return $this->fail('resource_limit', __('colony.error_insufficient_resources'), [
                'cost' => [self::RES_REGOLITH => $levelupRegolith],
            ]);
        }

        $newApSpend = min($row->ap_spend + 1, $effectiveApForLevelup);

        DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->update(['ap_spend' => $newApSpend]);

        if (! config('game.bypass.ap_checks')) {
            $this->advisorService->lockActionPoints($colony->id, 1);
        }

        if ($isCycleStart && $levelupRegolith > 0 && ! config('game.bypass.resource_costs')) {
            $this->resourcesService->payCosts(
                [['resource_id' => self::RES_REGOLITH, 'amount' => $levelupRegolith]],
                $colony->id
            );
        }

        $leveledUp = false;
        if ($newApSpend >= $effectiveApForLevelup) {
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
            // Consumes an active Cantina-Anliegen voucher (A41), if any — it
            // already discounted $effectiveApForLevelup above via
            // ProjectBonusService::effectiveApForLevelup(); this just marks it
            // spent so it doesn't apply again to the NEXT level-up.
            $this->projectBonusService->consumeActiveBuildingDiscountVoucher($colony->id);

            // Charakter-Kodex (A42) — Tomas (bartender, `permanent` game_role)
            // has no random-encounter trigger; his codex unlocks at Cantina
            // Ausbaustufen-Meilensteinen instead (level 1 -> entry 1, ...,
            // level 5 -> entry 5, via recordProgress()'s next-sequential slot).
            if ($buildingId === (int) config('buildings.bar.id', 52)) {
                $this->characterCodexService->recordProgress(Auth::id(), 'bartender');
            }
        }

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $this->getTick(),
            'event' => 'colony.building_invested',
            'area' => 'colony',
            'parameters' => json_encode([
                'building_id' => $buildingId,
                'instance_id' => $instanceId,
                'building_name' => $building->name ?? '',
                'ap_spend' => $newApSpend,
                'ap_for_levelup' => $effectiveApForLevelup,
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

        if (! config('game.bypass.ap_checks') && $this->advisorService->getAvailableActionPoints($colony->id) < 1) {
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
            $this->advisorService->lockActionPoints($colony->id, 1);
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
                'instance_id' => $instanceId,
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
     * What a Rückbau of one building instance would change — the numbers for the
     * confirmation dialog, computed by BuildingService::leveldownPreview() from the
     * real leveldown (nothing is written). Rejects exactly what leveldownBuilding()
     * rejects, with the same codes.
     */
    public function leveldownPreview(Request $request): JsonResponse
    {
        [$colonyId, $buildingId, $instanceId, $blocker] = $this->resolveLeveldownTarget($request);
        if ($blocker !== null) {
            return $this->fail($blocker);
        }

        return response()->json([
            'ok' => true,
            ...$this->buildingService->leveldownPreview($colonyId, $buildingId, $instanceId),
        ]);
    }

    /**
     * Rückbau (A43): lower one building instance of the player's own colony by one
     * level, free of charge — or cancel a placed level-0 construction site
     * (Bauabbruch: stays on 0, invested AP are forfeited). The rules live in
     * BuildingService::leveldown(): reaching level 0 takes the building off its tile
     * and drops the first-level workplace reserve, the Command Center never goes
     * below 1. Nothing that depends on the building is touched (Bestandsschutz), no
     * trust event. The freed supply and the colonist status are returned so the
     * resourcebar can sync live.
     */
    public function leveldownBuilding(Request $request): JsonResponse
    {
        [$colonyId, $buildingId, $instanceId, $blocker] = $this->resolveLeveldownTarget($request);
        if ($blocker !== null) {
            return $this->fail($blocker);
        }

        $before = $this->buildingService->getColonyEntity($colonyId, $buildingId, $instanceId);

        if (! $this->buildingService->leveldown($colonyId, $buildingId, $instanceId)) {
            return $this->fail($this->buildingService->leveldownBlocker($colonyId, $buildingId, $instanceId) ?? 'building_not_found');
        }

        $building = $this->fetchBuildingRow($colonyId, $buildingId, $instanceId);
        $tileReleased = $before?->tile_x !== null && $building->tile_x === null;

        return response()->json([
            'ok' => true,
            'level' => (int) $building->level,
            'construction_cancelled' => (int) $before?->level === 0,
            'tile_released' => $tileReleased,
            'released_tile' => $tileReleased ? ['q' => (int) $before->tile_x, 'r' => (int) $before->tile_y] : null,
            'building' => $building,
            'activeHint' => $this->resolveHint($colonyId),
            ...$this->currentAp($colonyId),
            'colonists' => $this->colonistsPayload($colonyId),
        ]);
    }

    /**
     * Validated Rückbau target of the player's own colony (colony from the session,
     * never from the request) plus the reason it cannot be levelled down, if any.
     *
     * @return array{int, int, int, string|null} [colonyId, buildingId, instanceId, blocker]
     */
    private function resolveLeveldownTarget(Request $request): array
    {
        $data = $request->validate([
            'building_id' => 'required|integer',
            'instance_id' => 'required|integer',
        ]);
        $colonyId = (int) $this->colonyService->getPrimeColony(Auth::id())->id;
        $buildingId = (int) $data['building_id'];
        $instanceId = (int) $data['instance_id'];

        $blocker = $this->buildingService->instanceBlocker($colonyId, $buildingId, $instanceId)
            ?? $this->buildingService->leveldownBlocker($colonyId, $buildingId, $instanceId);

        return [$colonyId, $buildingId, $instanceId, $blocker];
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

        $unitPrice = $this->nexusImportService->unitPrice($colony->id, NexusImportService::RES_COMPOUNDS);
        $totalCost = $amount * $unitPrice;

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
            'parameters' => json_encode(['colony_id' => $colony->id, 'amount' => $amount, 'cost' => $totalCost, 'unit_price' => $unitPrice]),
        ]);

        $credits = (int) (DB::table('user_resources')->where('user_id', $colony->user_id)->value('credits') ?? 0);
        $compounds = (int) (DB::table('colony_resources')->where('colony_id', $colony->id)->where('resource_id', 4)->value('amount') ?? 0);

        return response()->json([
            'ok' => true,
            'amount' => $amount,
            'cost' => $totalCost,
            'unit_price' => $unitPrice,
            'credits' => $credits,
            'compounds' => $compounds,
        ]);
    }

    /**
     * Delayed Uplink-Direktimport for Regolith/Organika (Owner-Entscheidung
     * F5/A28, 2026-09-08/11) — replaces the struck "Nexus-Handelsschiffe"
     * concept (§12 Kanal 2). Payment happens immediately like the Werkstoff
     * import above; the resource itself only arrives after
     * `game.economy.delayed_import_delivery_ticks[uplinkLevel]` Sole
     * (GameTick::processNexusImportDeliveries() credits it).
     */
    public function nexusImportResource(Request $request): JsonResponse
    {
        $data = $request->validate([
            'resource_id' => ['required', 'integer', Rule::in([NexusImportService::RES_REGOLITH, NexusImportService::RES_ORGANICS])],
            'amount' => 'required|integer|min:1|max:9999',
        ]);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $resourceId = (int) $data['resource_id'];
        $amount = (int) $data['amount'];

        $uplinkId = (int) config('buildings.uplinkStation.id', 54);
        $uplinkLevel = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $uplinkId)
            ->value('level') ?? 0);

        if ($uplinkLevel < 1) {
            return $this->fail('uplink_required', __('colony.nexus_import_uplink_required'));
        }

        $unitPrice = $this->nexusImportService->unitPrice($colony->id, $resourceId);
        $totalCost = $amount * $unitPrice;

        if (! $this->resourcesService->check([['resource_id' => ResourcesService::RES_CREDITS, 'amount' => $totalCost]], $colony->id)) {
            return $this->fail('credit_limit', __('colony.nexus_import_no_credits'));
        }

        $this->resourcesService->payCosts([['resource_id' => ResourcesService::RES_CREDITS, 'amount' => $totalCost]], $colony->id);

        $deliveryTicksByLevel = config('game.economy.delayed_import_delivery_ticks', [1 => 5, 2 => 4, 3 => 3]);
        $cappedLevel = min($uplinkLevel, max(array_keys($deliveryTicksByLevel)));
        $deliveryTicks = (int) ($deliveryTicksByLevel[$cappedLevel] ?? 5);
        $deliverAtTick = $this->getTick() + $deliveryTicks;

        DB::table('nexus_imports')->insert([
            'colony_id' => $colony->id,
            'resource_id' => $resourceId,
            'amount' => $amount,
            'deliver_at_tick' => $deliverAtTick,
        ]);

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $this->getTick(),
            'event' => 'colony.nexus_import_requested',
            'area' => 'colony',
            'parameters' => json_encode([
                'colony_id' => $colony->id,
                'resource_id' => $resourceId,
                'amount' => $amount,
                'cost' => $totalCost,
                'unit_price' => $unitPrice,
                'deliver_at_tick' => $deliverAtTick,
            ]),
        ]);

        $credits = (int) (DB::table('user_resources')->where('user_id', $colony->user_id)->value('credits') ?? 0);

        return response()->json([
            'ok' => true,
            'resource_id' => $resourceId,
            'amount' => $amount,
            'cost' => $totalCost,
            'unit_price' => $unitPrice,
            'credits' => $credits,
            'deliver_at_tick' => $deliverAtTick,
            'delivery_ticks' => $deliveryTicks,
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

    /**
     * "Wegschicken" (GDD §6 "Überkapazität — Konsequenzen", A14): every homeless
     * colonist of the player's own colony leaves at once for overcap.dismiss_ap_cost
     * AP from the shared pool — same result as the departure, but the streak ends
     * now and the milder trust event colonists_dismissed takes effect next Sol.
     * Only available while colonists are homeless. The colony always comes from
     * the session, never from the request.
     */
    public function dismissColonists(): JsonResponse
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        if ($this->resourcesService->colonistStatus($colony->id)['homeless'] <= 0) {
            return $this->fail('no_homeless_colonists');
        }

        $apCost = (int) config('game.overcap.dismiss_ap_cost', 8);
        $available = $this->advisorService->getAvailableActionPoints($colony->id);
        if (! config('game.bypass.ap_checks') && $available < $apCost) {
            return $this->fail('ap_limit', __('colony.onboarding_trigger_ap_limit'), [
                'current' => $available,
                'required' => $apCost,
            ]);
        }

        // Trust event + log target the next Sol — the Sol the event takes effect on
        // and whose Sol report shows it (same convention as purchaseStipend()).
        $targetTick = $this->getTick() + 1;

        $dismissed = DB::transaction(function () use ($colony, $apCost, $targetTick): int {
            if (! config('game.bypass.ap_checks')) {
                $this->advisorService->lockActionPoints($colony->id, $apCost);
            }

            return $this->overcapService->dismiss($colony->id, (int) Auth::id(), $targetTick);
        });

        return response()->json([
            'ok' => true,
            'dismissed' => $dismissed,
            ...$this->currentAp($colony->id),
            'colonists' => $this->colonistsPayload($colony->id),
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
    /**
     * Whether the supply build gate blocks the next level of the given building:
     * free supply (cap − workplaces) below its supply_cost. Command Center and
     * housing are always allowed (GDD §6 "Supply als Bau-Gate").
     */
    private function levelUpBlockedBySupply(int $colonyId, int $buildingId, int $supplyCost): bool
    {
        if (config('game.bypass.supply_checks') || $supplyCost <= 0) {
            return false;
        }

        if (in_array($buildingId, [BuildingId::CommandCenter->value, BuildingId::Housing->value], true)) {
            return false;
        }

        return $this->resourcesService->getFreeSupply($colonyId) < $supplyCost;
    }

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
            'apAvailable' => $this->advisorService->getAvailableActionPoints($colonyId),
            // Build-chip affordability check (greys out unaffordable buildings) needs
            // these alongside AP — kept on the same payload so every action that
            // refreshes AP also refreshes resources.
            'regolith' => (int) (DB::table('colony_resources')->where('colony_id', $colonyId)->where('resource_id', 3)->value('amount') ?? 0),
            'werkstoffe' => (int) (DB::table('colony_resources')->where('colony_id', $colonyId)->where('resource_id', 4)->value('amount') ?? 0),
            'freeSupply' => $this->resourcesService->getFreeSupply($colonyId),
        ];
    }

    /**
     * Colonist chip data for the resourcebar live sync (KOL chip).
     *
     * @return array{present: int, cap: int, homeless: int, departed: int, staffing_pct: int}
     */
    private function colonistsPayload(int $colonyId): array
    {
        $status = $this->resourcesService->colonistStatus($colonyId);

        return [
            'present' => $status['present'],
            'cap' => $status['cap'],
            'homeless' => $status['homeless'],
            'departed' => $status['departed'],
            'staffing_pct' => (int) round($status['staffing'] * 100),
        ];
    }

    /**
     * Same requirement format as TechtreeController::computeRequiredList() —
     * Owner-Playtest-Fund 2026-09-04: the hexview sidebar showed unlocks_next_level
     * but never what the building itself is gated behind. Buildings only ever carry
     * a single required_building_id/level pair (no required_building2_id, unlike
     * researches), so this is always 0 or 1 entries.
     *
     * @return list<string>
     */
    private function computeRequiredList(?int $reqBuildingId, ?int $reqLevel, Collection $buildingNames): array
    {
        if (! $reqBuildingId) {
            return [];
        }

        $reqName = $buildingNames[$reqBuildingId] ?? null;
        if (! $reqName) {
            return [];
        }

        return [__('techtree.'.$reqName).' Lv'.($reqLevel ?? 1)];
    }

    private function hexDistance(int $q1, int $r1, int $q2, int $r2): int
    {
        $dq = $q2 - $q1;
        $dr = $r2 - $r1;

        return (abs($dq) + abs($dr) + abs($dq + $dr)) / 2;
    }

    /**
     * Resolve the named tier beiname for a building at a given level, or null
     * if that level has no name (design-spec.md: "Beiname nur bei echtem
     * Fähigkeits-Sprung"). Looks the building's config key up by id (same
     * pattern as OnboardingHintService::canAffordBuildingPlacement()).
     */
    private function resolveTierLabel(int $buildingId, int $level): ?string
    {
        $key = collect(config('buildings'))->search(fn ($cfg) => $cfg['id'] === $buildingId);
        if ($key === false) {
            return null;
        }

        $tiers = config("buildings.{$key}.tiers", []);
        if (! in_array($level, $tiers, true)) {
            return null;
        }

        return __("techtree.tier_{$key}_{$level}");
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
                'buildings.required_building_id',
                'buildings.required_building_level',
            )
            ->first();

        $row->label = __('techtree.'.$row->building_key);
        $row->image_slug = self::buildingImageSlug($row->building_key);
        $row->description = __('buildings.'.preg_replace('/^building_/', '', $row->building_key).'_desc');
        $row->unlocks_next_level = $this->buildingUnlockService->unlocksAtLevel((int) $row->building_id, (int) $row->level + 1);
        $row->required_list = $this->computeRequiredList(
            $row->required_building_id !== null ? (int) $row->required_building_id : null,
            $row->required_building_level !== null ? (int) $row->required_building_level : null,
            DB::table('buildings')->pluck('name', 'id')
        );
        $row->in_transit = $row->pending_until_tick !== null && (int) $row->pending_until_tick >= $this->getTick();
        $row->levelup_cost = $this->buildingCostService->levelupRegolithDue($row);
        $row->first_level_prepaid = BuildingCostService::firstLevelPrepaid($row);
        $row->ap_for_levelup = $this->projectBonusService->effectiveApForLevelup($colonyId, (int) $row->ap_for_levelup);
        $row->tier_label = $this->resolveTierLabel((int) $row->building_id, (int) $row->level);

        return $row;
    }

    private static function buildingImageSlug(string $key): string
    {
        $key = preg_replace('/^building_/', '', $key);
        $overrides = ['bar' => 'cantina'];

        return $overrides[$key] ?? strtolower(preg_replace('/([A-Z])/', '-$1', $key));
    }
}
