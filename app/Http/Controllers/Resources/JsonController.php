<?php

namespace App\Http\Controllers\Resources;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Concerns\ResolvesActiveColony;
use App\Services\ResourcesService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class JsonController extends BaseController
{
    use ResolvesActiveColony;

    public function __construct(
        TickService $tick,
        private readonly ResourcesService $resources,
    ) {
        parent::__construct($tick);
    }

    /**
     * GET /resources/colony/{id}
     * Returns colony resource amounts as JSON, keyed by resource_id.
     */
    public function getColonyResources(int $id): JsonResponse
    {
        $result = $this->resources->getColonyResources(['colony_id' => $id])
            ->keyBy('resource_id')
            ->map(fn ($r) => ['resource_id' => $r->resource_id, 'amount' => $r->amount]);

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
        $colonyId = $this->resolveColonyId();

        $possessions = $this->resources->getPossessionsByColonyId($colonyId);
        $resourceTypes = $this->resources->getResources()->keyBy('id');

        // Merge resource metadata into possessions
        foreach ($possessions as $resId => $poss) {
            if (isset($resourceTypes[$resId])) {
                $possessions[$resId] = array_merge($poss, $resourceTypes[$resId]->toArray());
            }
        }

        return response()->view('resources.resourcebar', [
            'tick' => $this->getTick(),
            'possessions' => $possessions,
        ])->withHeaders(['X-IC-Refresh' => 'false']);
    }
}
