<?php

namespace App\Http\Controllers\Techtree;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\ResourcesService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\PersonellService;
use App\Services\Techtree\ResearchService;
use App\Services\Techtree\ShipService;
use App\Services\Techtree\TechtreeColonyService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TechtreeController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly BuildingService $buildingService,
        private readonly ResearchService $researchService,
        private readonly ShipService $shipService,
        private readonly PersonellService $personellService,
        private readonly TechtreeColonyService $techtreeColonyService,
        private readonly ResourcesService $resourcesService,
        private readonly ColonyService $colonyService,
    ) {
        parent::__construct($tick);
    }

    private function resolveColonyId(): int
    {
        $colonyId = Session::get('activeIds.colonyId');
        if ($colonyId) {
            return (int) $colonyId;
        }
        $userId = $this->getCurrentUserId();
        $colony = $this->colonyService->getPrimeColony($userId);
        $id = $colony->id;
        Session::put('activeIds.colonyId', $id);
        return $id;
    }

    /**
     * Display the full techtree overview for the active colony.
     */
    public function index()
    {
        $colonyId = $this->resolveColonyId();
        $techtree = $this->techtreeColonyService->getTechtree($colonyId);
        return view('techtree.index', compact('techtree', 'colonyId'));
    }

    /**
     * Return a technology detail partial (AJAX popup, no layout).
     */
    public function technology(string $type, int $id): \Illuminate\View\View
    {
        $colonyId = $this->resolveColonyId();
        $techtree = $this->techtreeColonyService->getTechtree($colonyId);

        $service = match (strtolower($type)) {
            'building'  => $this->buildingService,
            'research'  => $this->researchService,
            'ship'      => $this->shipService,
            'personell' => $this->personellService,
            default     => throw new \InvalidArgumentException("Unknown type: $type"),
        };

        $apType = match (strtolower($type)) {
            'research' => 'research',
            default    => 'construction',
        };

        return view('techtree.technology', [
            'type'                   => $type,
            'techId'                 => $id,
            'tech'                   => $techtree[$type][$id] ?? null,
            'costs'                  => $service->getEntityCosts($id),
            'resources'              => $this->resourcesService->getResources()->keyBy('id'),
            'apAvailable'            => $this->personellService->getAvailableActionPoints($apType, $colonyId),
            'requiredBuildingsCheck' => $service->checkRequiredBuildingsByEntityId($colonyId, $id),
            'requiredResourcesCheck' => $this->resourcesService->check($service->getEntityCosts($id), $colonyId),
            // Passed so the view can resolve required building/research names
            'buildings'              => $techtree['building'],
            'researches'             => $techtree['research'],
        ]);
    }

    /**
     * Perform a techtree action via GET and return the refreshed technology partial.
     *
     * Called by techtree.js via AJAX: GET /techtree/{type}/{id}/{order}[/{ap}]
     * e.g. /techtree/building/25/add/3   or   /techtree/building/25/levelup
     */
    public function action(string $type, int $id, string $order, int $ap = 1): \Illuminate\View\View
    {
        $colonyId = $this->resolveColonyId();

        $service = match (strtolower($type)) {
            'building'  => $this->buildingService,
            'research'  => $this->researchService,
            'ship'      => $this->shipService,
            'personell' => $this->personellService,
            default     => throw new \InvalidArgumentException("Unknown type: $type"),
        };

        match ($order) {
            'add', 'repair', 'remove' => $service->invest($colonyId, $id, $order, $ap),
            'levelup'                 => $service->levelup($colonyId, $id),
            'leveldown'               => $service->leveldown($colonyId, $id),
            default                   => null,
        };

        // Re-render the technology partial so the modal reflects the updated state
        return $this->technology($type, $id);
    }

    /**
     * Perform a techtree order (invest AP, levelup, or leveldown) via POST.
     */
    public function order(Request $request, string $type, int $id): JsonResponse
    {
        $colonyId = $this->resolveColonyId();
        $order    = $request->input('order');
        $ap       = (int) $request->input('ap', 1);

        $service = match (strtolower($type)) {
            'building'  => $this->buildingService,
            'research'  => $this->researchService,
            'ship'      => $this->shipService,
            'personell' => $this->personellService,
            default     => null,
        };

        if (!$service) {
            return response()->json(['success' => false, 'message' => 'Unknown type']);
        }

        $result = match ($order) {
            'add', 'repair', 'remove' => $service->invest($colonyId, $id, $order, $ap),
            'levelup'                 => $service->levelup($colonyId, $id),
            'leveldown'               => $service->leveldown($colonyId, $id),
            default                   => false,
        };

        return response()->json(['success' => (bool) $result, 'order' => $order]);
    }
}
