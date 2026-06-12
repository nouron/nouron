<?php

namespace App\Http\Controllers\Colony;

use App\Enums\BuildingId;
use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\ColonyTileService;
use App\Services\EventService;
use App\Services\MerchantService;
use App\Services\OnboardingHintService;
use App\Services\OnboardingTriggerService;
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
    ) {
        parent::__construct($tick);
    }

    public function index(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        return view('colony.index', compact('colony'));
    }

    public function hexview(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $tiles  = $this->tileService->getTilesForColony($colony->id);

        if ($tiles->isEmpty()) {
            $this->tileService->generateDefaultTiles($colony);
            $tiles = $this->tileService->getTilesForColony($colony->id);
        }

        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->value('level') ?? 0;

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
                $b->label      = __('techtree.' . $b->building_key);
                $b->image_slug = self::buildingImageSlug($b->building_key);
                $b->in_transit = $b->pending_until_tick !== null && (int) $b->pending_until_tick >= $globalTick;
                return $b;
            });

        $navAp          = $this->personellService->getAvailableActionPoints('navigation', $colony->id);
        $constructionAp = $this->personellService->getAvailableActionPoints('construction', $colony->id);
        $activeHint     = $this->resolveHint($colony->id);

        $fireds        = json_decode(DB::table('user_preferences')->where('user_id', Auth::id())->value('fired_triggers') ?? '[]', true) ?? [];
        $supplyCapFull = in_array('supply_cap_full', $fireds);

        $trust      = (int) (DB::table('colony_resources')->where('colony_id', $colony->id)->where('resource_id', 12)->value('amount') ?? 0);
        $currentSol = max(1, $globalTick - (int) $colony->since_tick + 1);
        $solLimit   = (int) config('game.run.tick_limit', 100);

        $merchantVisit = $this->merchantService->getActiveVisit($colony->id, $globalTick);
        $merchantItems = $merchantVisit
            ? $this->merchantService->getItemsForVisit($merchantVisit->id)->values()->toArray()
            : [];

        return view('colony.hexview', compact('colony', 'tiles', 'ccLevel', 'buildings', 'navAp', 'constructionAp', 'activeHint', 'supplyCapFull', 'trust', 'currentSol', 'solLimit', 'merchantVisit', 'merchantItems'));
    }

    // ── Tile actions ──────────────────────────────────────────────────────────

    public function exploreTile(Request $request): JsonResponse
    {
        $data   = $request->validate(['q' => 'required|integer', 'r' => 'required|integer']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $result = $this->tileService->exploreTile($colony->id, (int) $data['q'], (int) $data['r']);

        if ($result['ok']) {
            $this->eventService->createEvent([
                'user'       => Auth::id(),
                'tick'       => $this->getTick(),
                'event'      => 'colony.tile_explored',
                'area'       => 'colony',
                'parameters' => json_encode(['colony_id' => $colony->id]),
            ]);
        }

        $extra = $result['ok'] ? [...$this->currentAp($colony->id), 'activeHint' => $this->resolveHint($colony->id)] : [];
        return response()->json([...$result, ...$extra]);
    }

    public function deepScanTile(Request $request): JsonResponse
    {
        $data   = $request->validate(['q' => 'required|integer', 'r' => 'required|integer']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $result = $this->tileService->deepScanTile($colony->id, (int) $data['q'], (int) $data['r']);

        if ($result['ok']) {
            $this->eventService->createEvent([
                'user'       => Auth::id(),
                'tick'       => $this->getTick(),
                'event'      => 'colony.tile_deep_scanned',
                'area'       => 'colony',
                'parameters' => json_encode(['colony_id' => $colony->id]),
            ]);
        }

        $extra = $result['ok'] ? [...$this->currentAp($colony->id), 'activeHint' => $this->resolveHint($colony->id)] : [];
        return response()->json([...$result, ...$extra]);
    }

    // ── Building actions ──────────────────────────────────────────────────────

    public function availableBuildings(): JsonResponse
    {
        $colony  = $this->colonyService->getPrimeColony(Auth::id());
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', BuildingId::CommandCenter->value)->value('level') ?? 0;

        $placedCounts = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->whereNotNull('tile_x')
            ->selectRaw('building_id, COUNT(*) as cnt')
            ->groupBy('building_id')
            ->pluck('cnt', 'building_id')
            ->toArray();

        $buildings = DB::table('buildings')
            ->select('id', 'name', 'ap_for_levelup', 'max_status_points', 'max_level',
                     'required_building_id', 'required_building_level', 'is_instanced', 'supply_cost')
            ->get()
            ->filter(function ($b) use ($ccLevel, $placedCounts) {
                if ($b->id === BuildingId::CommandCenter->value) return false;  // CC — already exists
                if ($b->id === BuildingId::Harvester->value) return false;  // Harvester — regolith placement only
                $count = $placedCounts[$b->id] ?? 0;
                if ($b->is_instanced) {
                    if ($count >= ($b->max_level ?? PHP_INT_MAX)) return false;
                } else {
                    if ($count > 0) return false;
                }
                if ($b->required_building_id === BuildingId::CommandCenter->value && $ccLevel < (int) ($b->required_building_level ?? 1)) {
                    return false;
                }
                return true;
            })
            ->map(fn($b) => [
                'building_id'       => $b->id,
                'key'               => $b->name,
                'label'             => __('techtree.' . $b->name),
                'ap_for_levelup'    => $b->ap_for_levelup,
                'max_level'         => $b->max_level,
                'max_status_points' => $b->max_status_points,
                'is_instanced'      => (bool) $b->is_instanced,
                'supply_cost'       => (int) $b->supply_cost,
            ])
            ->values();

        return response()->json(['buildings' => $buildings]);
    }

    public function placeBuilding(Request $request): JsonResponse
    {
        $data = $request->validate([
            'building_id' => 'required|integer',
            'q'           => 'required|integer',
            'r'           => 'required|integer',
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        $tile = DB::table('colony_tiles')
            ->where('colony_id', $colony->id)
            ->where('q', $data['q'])
            ->where('r', $data['r'])
            ->first();

        if (!$tile)
            return response()->json(['ok' => false, 'error' => __('colony.error_tile_not_found')]);
        if (!$tile->is_explored)
            return response()->json(['ok' => false, 'error' => __('colony.error_not_explored')]);

        $isHarvester = (int) $data['building_id'] === BuildingId::Harvester->value;

        if ($isHarvester) {
            // Harvester goes to regolith tiles in the exploration zone (ring 3+, is_colony_zone=0).
            if (!str_starts_with($tile->tile_type, 'regolith_'))
                return response()->json(['ok' => false, 'error' => __('colony.error_harvester_needs_regolith')]);
        } else {
            // Regular buildings: colony zone only, buildable terrain.
            if (!$tile->is_colony_zone)
                return response()->json(['ok' => false, 'error' => __('colony.error_tile_outside_colony')]);
            if (!str_starts_with($tile->tile_type, 'terrain_') || $tile->tile_type === 'terrain_impassable')
                return response()->json(['ok' => false, 'error' => __('colony.error_tile_not_buildable')]);
        }

        $occupied = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('tile_x', $data['q'])
            ->where('tile_y', $data['r'])
            ->exists();
        if ($occupied)
            return response()->json(['ok' => false, 'error' => __('colony.error_tile_occupied')]);

        $building = DB::table('buildings')->where('id', $data['building_id'])->first();
        if (!$building)
            return response()->json(['ok' => false, 'error' => __('colony.error_building_not_found')]);

        // Harvester is marked is_instanced=1 in schema but has exactly one instance per colony
        // and must always be moved (UPDATE), never duplicated (INSERT).
        $existingBuilding = ($isHarvester || !$building->is_instanced)
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

        $apCost = $isHarvesterMove
            ? max(1, $this->hexDistance((int)$existingBuilding->tile_x, (int)$existingBuilding->tile_y, (int)$data['q'], (int)$data['r']))
            : 1;

        if (!config('game.bypass.ap_checks') && $this->personellService->getConstructionPoints($colony->id) < $apCost)
            return response()->json([
                'ok'      => false,
                'error'   => 'ap_limit',
                'ap_type' => 'construction',
                'current' => $this->personellService->getConstructionPoints($colony->id),
                'message' => __('colony.onboarding_trigger_ap_limit'),
            ]);

        if ($building->is_instanced && !$isHarvester) {
            $nextInstanceId = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', $data['building_id'])
                ->max('instance_id') + 1;
            DB::table('colony_buildings')->insert([
                'colony_id'     => $colony->id,
                'building_id'   => $data['building_id'],
                'instance_id'   => $nextInstanceId,
                'level'         => 0,
                'status_points' => $building->max_status_points ?? 20,
                'ap_spend'      => 1,
                'tile_x'        => $data['q'],
                'tile_y'        => $data['r'],
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
                } elseif (!$isHarvesterMove) {
                    $update['ap_spend'] = 1;
                }
                // Harvester move: tile updates, ap_spend unchanged.
                // Relocation takes 1 Sol — no production until arrival.
                if ($isHarvesterMove) {
                    $update['pending_until_tick'] = $this->getTick() + 1;
                }
                DB::table('colony_buildings')
                    ->where('colony_id', $colony->id)
                    ->where('building_id', $data['building_id'])
                    ->update($update);
                $nextInstanceId = (int) $existingBuilding->instance_id;
            } else {
                DB::table('colony_buildings')->insert([
                    'colony_id'     => $colony->id,
                    'building_id'   => $data['building_id'],
                    'instance_id'   => 1,
                    'level'         => 0,
                    'status_points' => $building->max_status_points ?? 20,
                    'ap_spend'      => 1,
                    'tile_x'        => $data['q'],
                    'tile_y'        => $data['r'],
                ]);
            }
        }

        if (!config('game.bypass.ap_checks'))
            $this->personellService->lockActionPoints('construction', $colony->id, $apCost);

        $this->eventService->createEvent([
            'user'       => Auth::id(),
            'tick'       => $this->getTick(),
            'event'      => 'colony.building_placed',
            'area'       => 'colony',
            'parameters' => json_encode(['colony_id' => $colony->id, 'building_id' => $data['building_id']]),
        ]);

        $row = $this->fetchBuildingRow($colony->id, $data['building_id'], $nextInstanceId);

        // Harvester relocation: append onboarding tip flag once per user.
        if ((int) $data['building_id'] === BuildingId::Harvester->value) {
            $showTip = !$this->onboardingTriggerService->hasFired(Auth::id(), 'harvester_move_shown');
            $this->onboardingTriggerService->markFired(Auth::id(), 'harvester_move_shown');

            return response()->json([
                'ok'                  => true,
                'building'            => $row,
                'showHarvesterMoveTip' => $showTip,
                ...$this->currentAp($colony->id),
                'activeHint'          => $this->resolveHint($colony->id),
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
        $colony     = $this->colonyService->getPrimeColony(Auth::id());
        $buildingId = (int) $data['building_id'];
        $instanceId = (int) ($data['instance_id'] ?? 1);

        if (!config('game.bypass.ap_checks') && $this->personellService->getConstructionPoints($colony->id) < 1)
            return response()->json([
                'ok'      => false,
                'error'   => 'ap_limit',
                'ap_type' => 'construction',
                'current' => 0,
                'message' => __('colony.onboarding_trigger_ap_limit'),
            ]);

        $row = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->first();

        if (!$row)
            return response()->json(['ok' => false, 'error' => __('colony.error_building_not_found')]);

        $building = DB::table('buildings')->where('id', $buildingId)->first();

        if ($building->max_level !== null && $row->level >= (int) $building->max_level)
            return response()->json(['ok' => false, 'error' => __('colony.error_max_level_reached')]);

        $newApSpend = min($row->ap_spend + 1, $building->ap_for_levelup);

        DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->update(['ap_spend' => $newApSpend]);

        if (!config('game.bypass.ap_checks'))
            $this->personellService->lockActionPoints('construction', $colony->id, 1);

        $leveledUp = false;
        if ($newApSpend >= $building->ap_for_levelup) {
            DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', $buildingId)
                ->where('instance_id', $instanceId)
                ->update([
                    'level'         => $row->level + 1,
                    'ap_spend'      => 0,
                    'status_points' => $building->max_status_points ?? 20,
                ]);
            $leveledUp = true;
        }

        $this->eventService->createEvent([
            'user'       => Auth::id(),
            'tick'       => $this->getTick(),
            'event'      => 'colony.building_invested',
            'area'       => 'colony',
            'parameters' => json_encode([
                'building_id'    => $buildingId,
                'building_name'  => $building->name ?? '',
                'ap_spend'       => $newApSpend,
                'ap_for_levelup' => (int) $building->ap_for_levelup,
                'level_up'       => $leveledUp,
                'new_level'      => $leveledUp ? $row->level + 1 : $row->level,
            ]),
        ]);

        // CC level-up: recalculate colony zone and include updated tiles in response
        if ($leveledUp && $buildingId === BuildingId::CommandCenter->value) {
            $this->tileService->assignColonyZone($colony->id, $row->level + 1);
            $tiles = $this->tileService->getTilesForColony($colony->id)->values()->toArray();
            return response()->json([
                'ok'         => true,
                'building'   => $this->fetchBuildingRow($colony->id, $buildingId, $instanceId),
                'leveled_up' => true,
                'tiles'      => $tiles,
                'activeHint' => $this->resolveHint($colony->id),
                ...$this->currentAp($colony->id),
            ]);
        }

        return response()->json([
            'ok'         => true,
            'building'   => $this->fetchBuildingRow($colony->id, $buildingId, $instanceId),
            'leveled_up' => $leveledUp,
            'activeHint' => $this->resolveHint($colony->id),
            ...$this->currentAp($colony->id),
        ]);
    }

    public function dismissHint(Request $request): JsonResponse
    {
        $data   = $request->validate(['hint_key' => 'required|string|max:20']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $this->hintService->dismissHint(Auth::id(), $data['hint_key']);
        $activeHint = $this->hintService->getActiveHint($colony->id, Auth::id());

        return response()->json(['ok' => true, 'hint' => $activeHint]);
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

        return redirect()->route('colony.index')
            ->with('success', 'Kolonienname wurde aktualisiert.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveHint(int $colonyId): ?array
    {
        $hint = $this->hintService->getActiveHint($colonyId, Auth::id());
        if ($hint) $hint['text'] = __($hint['text_key']);
        return $hint;
    }

    private function currentAp(int $colonyId): array
    {
        return [
            'apNav'          => $this->personellService->getAvailableActionPoints('navigation', $colonyId),
            'apConstruction' => $this->personellService->getAvailableActionPoints('construction', $colonyId),
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

        $row->label      = __('techtree.' . $row->building_key);
        $row->image_slug = self::buildingImageSlug($row->building_key);
        $row->in_transit = $row->pending_until_tick !== null && (int) $row->pending_until_tick >= $this->getTick();

        return $row;
    }

    private static function buildingImageSlug(string $key): string
    {
        $key = preg_replace('/^building_/', '', $key);
        $overrides = ['bar' => 'cantina'];
        return $overrides[$key] ?? strtolower(preg_replace('/([A-Z])/', '-$1', $key));
    }
}
