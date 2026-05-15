<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\ColonyTileService;
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
            ->where('building_id', 25)
            ->value('level') ?? 0;

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
                'buildings.name as building_key',
                'buildings.max_level',
                'buildings.ap_for_levelup',
                'buildings.max_status_points',
            )
            ->get()
            ->map(function ($b) {
                $b->label      = __('techtree.' . $b->building_key);
                $b->image_slug = self::buildingImageSlug($b->building_key);
                return $b;
            });

        $navAp          = $this->personellService->getAvailableActionPoints('navigation', $colony->id);
        $constructionAp = $this->personellService->getAvailableActionPoints('construction', $colony->id);
        $activeHint     = $this->resolveHint($colony->id);

        $fireds        = json_decode(DB::table('user_preferences')->where('user_id', Auth::id())->value('fired_triggers') ?? '[]', true) ?? [];
        $supplyCapFull = in_array('supply_cap_full', $fireds);

        return view('colony.hexview', compact('colony', 'tiles', 'ccLevel', 'buildings', 'navAp', 'constructionAp', 'activeHint', 'supplyCapFull'));
    }

    // ── Tile actions ──────────────────────────────────────────────────────────

    public function exploreTile(Request $request): JsonResponse
    {
        $data   = $request->validate(['q' => 'required|integer', 'r' => 'required|integer']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $result = $this->tileService->exploreTile($colony->id, (int) $data['q'], (int) $data['r']);

        $extra = $result['ok'] ? [...$this->currentAp($colony->id), 'activeHint' => $this->resolveHint($colony->id)] : [];
        return response()->json([...$result, ...$extra]);
    }

    public function deepScanTile(Request $request): JsonResponse
    {
        $data   = $request->validate(['q' => 'required|integer', 'r' => 'required|integer']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $result = $this->tileService->deepScanTile($colony->id, (int) $data['q'], (int) $data['r']);

        $extra = $result['ok'] ? [...$this->currentAp($colony->id), 'activeHint' => $this->resolveHint($colony->id)] : [];
        return response()->json([...$result, ...$extra]);
    }

    // ── Building actions ──────────────────────────────────────────────────────

    public function availableBuildings(): JsonResponse
    {
        $colony  = $this->colonyService->getPrimeColony(Auth::id());
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', 25)->value('level') ?? 0;

        $placedCounts = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->whereNotNull('tile_x')
            ->selectRaw('building_id, COUNT(*) as cnt')
            ->groupBy('building_id')
            ->pluck('cnt', 'building_id')
            ->toArray();

        $buildings = DB::table('buildings')
            ->select('id', 'name', 'ap_for_levelup', 'max_status_points', 'max_level',
                     'required_building_id', 'required_building_level', 'is_instanced')
            ->get()
            ->filter(function ($b) use ($ccLevel, $placedCounts) {
                if ($b->id === 25) return false;  // CC — already exists
                if ($b->id === 27) return false;  // Harvester — regolith placement only
                $count = $placedCounts[$b->id] ?? 0;
                if ($b->is_instanced) {
                    if ($count >= ($b->max_level ?? PHP_INT_MAX)) return false;
                } else {
                    if ($count > 0) return false;
                }
                if ($b->required_building_id === 25 && $ccLevel < (int) ($b->required_building_level ?? 1)) {
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
        if (!$tile->is_colony_zone)
            return response()->json(['ok' => false, 'error' => __('colony.error_tile_outside_colony')]);
        if (!str_starts_with($tile->tile_type, 'terrain_') || $tile->tile_type === 'terrain_impassable')
            return response()->json(['ok' => false, 'error' => __('colony.error_tile_not_buildable')]);

        $occupied = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('tile_x', $data['q'])
            ->where('tile_y', $data['r'])
            ->exists();
        if ($occupied)
            return response()->json(['ok' => false, 'error' => __('colony.error_tile_occupied')]);

        if (!config('game.bypass.ap_checks') && $this->personellService->getConstructionPoints($colony->id) < 1)
            return response()->json([
                'ok'      => false,
                'error'   => 'ap_limit',
                'ap_type' => 'construction',
                'current' => 0,
                'message' => __('colony.onboarding_trigger_ap_limit'),
            ]);

        $building = DB::table('buildings')->where('id', $data['building_id'])->first();
        if (!$building)
            return response()->json(['ok' => false, 'error' => __('colony.error_building_not_found')]);

        if ($building->is_instanced) {
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

            if ($existing) {
                DB::table('colony_buildings')
                    ->where('colony_id', $colony->id)
                    ->where('building_id', $data['building_id'])
                    ->update(['tile_x' => $data['q'], 'tile_y' => $data['r'], 'ap_spend' => 1]);
                $nextInstanceId = (int) $existing->instance_id;
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
            $this->personellService->lockActionPoints('construction', $colony->id, 1);

        $row = $this->fetchBuildingRow($colony->id, $data['building_id'], $nextInstanceId);

        // Harvester relocation (building_id 27): append onboarding tip flag once per user.
        if ((int) $data['building_id'] === 27) {
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
                ->update(['level' => $row->level + 1, 'ap_spend' => 0]);
            $leveledUp = true;
        }

        // CC level-up: recalculate colony zone and include updated tiles in response
        if ($leveledUp && $buildingId === 25) {
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
                'buildings.name as building_key',
                'buildings.max_level',
                'buildings.ap_for_levelup',
                'buildings.max_status_points',
            )
            ->first();

        $row->label      = __('techtree.' . $row->building_key);
        $row->image_slug = self::buildingImageSlug($row->building_key);

        return $row;
    }

    private static function buildingImageSlug(string $key): string
    {
        $key = preg_replace('/^building_/', '', $key);
        $overrides = ['bar' => 'cantina'];
        return $overrides[$key] ?? strtolower(preg_replace('/([A-Z])/', '-$1', $key));
    }
}
