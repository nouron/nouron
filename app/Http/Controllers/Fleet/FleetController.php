<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\FleetService;
use App\Services\ResourcesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FleetController extends BaseController
{
    public function __construct(
        private readonly FleetService     $fleetService,
        private readonly ColonyService    $colonyService,
        private readonly ResourcesService $resourcesService,
    ) {}

    public function index(Request $request)
    {
        $userId   = $this->getCurrentUserId();
        $systemId = $request->route('sid');
        $objectId = $request->route('pid');
        $colonyId = $request->route('cid');
        $x        = $request->route('x');
        $y        = $request->route('y');

        if ($x && $y) {
            $ownFleets = $this->fleetService->getFleetsByCoords([$x, $y]);
        } elseif ($colonyId) {
            $ownFleets = $this->fleetService->getFleetsByEntityId('colony', $colonyId);
        } elseif ($objectId) {
            $ownFleets = $this->fleetService->getFleetsByEntityId('object', $objectId);
        } elseif ($systemId) {
            $ownFleets = $this->fleetService->getFleetsByEntityId('system', $systemId);
        } else {
            $ownFleets = $this->fleetService->getFleetsByUserId($userId);
        }

        return view('fleet.index', compact('ownFleets', 'userId'));
    }

    public function config(int $id)
    {
        $userId = $this->getCurrentUserId();
        $fleet  = $this->fleetService->getFleet($id);

        if (!$fleet) {
            abort(404, 'Fleet not found.');
        }

        $colony = $this->colonyService->getColonyByCoords($fleet->getCoords());
        $fleetIsInColonyOrbit = $colony !== false;

        $resources = $this->resourcesService->getResources()->keyBy('id');

        return view('fleet.config', compact('fleet', 'colony', 'fleetIsInColonyOrbit', 'resources'));
    }

    // ── JSON endpoints ────────────────────────────────────────────────────────

    public function addToFleet(Request $request): JsonResponse
    {
        $fleetId  = (int) $request->post('id', 0);
        $itemType = $request->post('itemType');
        $itemId   = (int) $request->post('itemId', 0);
        $amount   = (int) $request->post('amount', 0);

        $fleet  = $this->fleetService->getFleet($fleetId);
        $colony = $fleet ? $this->colonyService->getColonyByCoords($fleet->getCoords()) : null;

        $transferred = match (strtolower((string) $itemType)) {
            'ship'     => $this->fleetService->transferShip($colony, $fleet, $itemId, $amount),
            'research' => $this->fleetService->transferResearch($colony, $fleet, $itemId, $amount),
            'personell'=> $this->fleetService->transferPersonell($colony, $fleet, $itemId, $amount),
            'resource' => $this->fleetService->transferResource($colony, $fleet, $itemId, $amount),
            default    => 0,
        };

        return response()->json([
            'colonyId'    => $colony?->id,
            'fleetId'     => $fleetId,
            'itemType'    => $itemType,
            'itemId'      => $itemId,
            'transferred' => $transferred,
        ]);
    }

    public function getFleetTechnologies(int $id): JsonResponse
    {
        $techs = $this->fleetService->getFleetTechnologies($id);
        return response()->json($techs);
    }

    public function getFleetResources(int $id): JsonResponse
    {
        $resources    = $this->resourcesService->getResources()->keyBy('id');
        $fleetResources = $this->fleetService->getFleetResourcesByFleetId($id);

        $result = $fleetResources->keyBy('resource_id')->map(function ($row) use ($resources) {
            return array_merge($row->toArray(), [
                'name' => $resources->get($row->resource_id)?->name ?? '',
            ]);
        });

        return response()->json($result);
    }
}
