<?php

namespace App\Http\Controllers\Resources;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\ResourcesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class JsonController extends BaseController
{
    public function __construct(
        private readonly ResourcesService $resources,
        private readonly ColonyService $colonies,
    ) {
        parent::__construct();
        $this->middleware('auth');
    }

    /**
     * GET /resources/colony/{id}
     * Returns colony resource amounts as JSON, keyed by resource_id.
     */
    public function getColonyResources(int $id): JsonResponse
    {
        $result = $this->resources->getColonyResources(['colony_id' => $id])
            ->keyBy('resource_id')
            ->map(fn($r) => ['resource_id' => $r->resource_id, 'amount' => $r->amount]);

        return response()->json($result);
    }

    /**
     * GET /resources
     * Returns all resource type definitions, keyed by id.
     */
    public function getResources(): JsonResponse
    {
        $result = $this->resources->getResources()->keyBy('id');
        return response()->json($result);
    }

    /**
     * GET /resources/resourcebar
     * Returns the resource bar HTML partial (no layout — replaces setTerminal(true)).
     */
    public function reloadResourceBar(): Response
    {
        $colonyId = session('activeIds.colonyId', 1);

        $possessions = $this->resources->getPossessionsByColonyId($colonyId);
        $resourceTypes = $this->resources->getResources()->keyBy('id');

        // Merge resource metadata into possessions
        foreach ($possessions as $resId => $poss) {
            if (isset($resourceTypes[$resId])) {
                $possessions[$resId] = array_merge($poss, $resourceTypes[$resId]->toArray());
            }
        }

        return response()->view('resources.resourcebar', [
            'tick'        => $this->getTick(),
            'possessions' => $possessions,
        ])->withHeaders(['X-IC-Refresh' => 'false']);
    }
}
