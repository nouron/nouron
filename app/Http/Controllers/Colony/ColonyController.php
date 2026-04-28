<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\ColonyTileService;
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
                $b->label = __('techtree.' . $b->building_key);
                return $b;
            });

        return view('colony.hexview', compact('colony', 'tiles', 'ccLevel', 'buildings'));
    }

    // ── Tile actions ──────────────────────────────────────────────────────────

    public function exploreTile(Request $request): JsonResponse
    {
        $data   = $request->validate(['q' => 'required|integer', 'r' => 'required|integer']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        return response()->json(
            $this->tileService->exploreTile($colony->id, (int) $data['q'], (int) $data['r'])
        );
    }

    public function deepScanTile(Request $request): JsonResponse
    {
        $data   = $request->validate(['q' => 'required|integer', 'r' => 'required|integer']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        return response()->json(
            $this->tileService->deepScanTile($colony->id, (int) $data['q'], (int) $data['r'])
        );
    }

    // ── Building actions ──────────────────────────────────────────────────────

    public function availableBuildings(): JsonResponse
    {
        $colony  = $this->colonyService->getPrimeColony(Auth::id());
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', 25)->value('level') ?? 0;

        // Building IDs already placed on a tile
        $placed = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->whereNotNull('tile_x')
            ->pluck('building_id')
            ->toArray();

        $buildings = DB::table('buildings')
            ->select('id', 'name', 'ap_for_levelup', 'max_status_points', 'max_level',
                     'required_building_id', 'required_building_level')
            ->get()
            ->filter(function ($b) use ($ccLevel, $placed) {
                if ($b->id === 25) return false;  // CC — already exists
                if ($b->id === 27) return false;  // Harvester — special regolith placement
                if (in_array($b->id, $placed)) return false;
                // Prerequisite: if requires CC, check CC level
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
        if (!str_starts_with($tile->tile_type, 'terrain_') || $tile->tile_type === 'terrain_impassable')
            return response()->json(['ok' => false, 'error' => __('colony.error_tile_not_buildable')]);

        $occupied = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('tile_x', $data['q'])
            ->where('tile_y', $data['r'])
            ->exists();
        if ($occupied)
            return response()->json(['ok' => false, 'error' => __('colony.error_tile_occupied')]);

        if ($this->personellService->getConstructionPoints($colony->id) < 1)
            return response()->json(['ok' => false, 'error' => __('colony.error_no_construction_ap')]);

        $building = DB::table('buildings')->where('id', $data['building_id'])->first();
        if (!$building)
            return response()->json(['ok' => false, 'error' => __('colony.error_building_not_found')]);

        $existing = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $data['building_id'])
            ->first();

        if ($existing) {
            DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', $data['building_id'])
                ->update(['tile_x' => $data['q'], 'tile_y' => $data['r'], 'ap_spend' => 1]);
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

        $this->personellService->lockActionPoints('construction', $colony->id, 1);

        $row = $this->fetchBuildingRow($colony->id, $data['building_id']);

        return response()->json(['ok' => true, 'building' => $row]);
    }

    public function investBuilding(Request $request): JsonResponse
    {
        $data   = $request->validate(['building_id' => 'required|integer']);
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        if ($this->personellService->getConstructionPoints($colony->id) < 1)
            return response()->json(['ok' => false, 'error' => __('colony.error_no_construction_ap')]);

        $row = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $data['building_id'])
            ->first();

        if (!$row)
            return response()->json(['ok' => false, 'error' => __('colony.error_building_not_found')]);

        $building   = DB::table('buildings')->where('id', $data['building_id'])->first();
        $newApSpend = min($row->ap_spend + 1, $building->ap_for_levelup);

        DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', $data['building_id'])
            ->update(['ap_spend' => $newApSpend]);

        $this->personellService->lockActionPoints('construction', $colony->id, 1);

        $leveledUp = false;
        if ($newApSpend >= $building->ap_for_levelup) {
            DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', $data['building_id'])
                ->update(['level' => $row->level + 1, 'ap_spend' => 0]);
            $leveledUp = true;
        }

        return response()->json([
            'ok'        => true,
            'building'  => $this->fetchBuildingRow($colony->id, $data['building_id']),
            'leveled_up' => $leveledUp,
        ]);
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

    private function fetchBuildingRow(int $colonyId, int $buildingId): object
    {
        $row = DB::table('colony_buildings')
            ->join('buildings', 'colony_buildings.building_id', '=', 'buildings.id')
            ->where('colony_buildings.colony_id', $colonyId)
            ->where('colony_buildings.building_id', $buildingId)
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

        $row->label = __('techtree.' . $row->building_key);

        return $row;
    }
}
