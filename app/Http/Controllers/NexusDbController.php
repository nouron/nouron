<?php

namespace App\Http\Controllers;

use App\Enums\BuildingId;
use App\Services\BuildingCostService;
use App\Services\OnboardingHintService;
use App\Services\PathBuildingGateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NexusDbController extends Controller
{
    public function __construct(
        private readonly OnboardingHintService $onboardingHintService,
        private readonly BuildingCostService $buildingCosts,
    ) {}

    /** First-visit flag plus the building catalog; concept texts come from the lang files. */
    public function data(): JsonResponse
    {
        $userId = Auth::id();

        return response()->json([
            'first_visit' => $userId ? $this->onboardingHintService->checkFirstVisit('nexusdb', $userId) : false,
            'buildings' => $this->buildingCatalog(),
        ]);
    }

    /**
     * Reference entry per active building, independent of what the colony can build right now:
     * description, requirement, costs ([resource_id => amount]) and the per-level AP/Regolith steps.
     *
     * @return list<array<string, mixed>>
     */
    private function buildingCatalog(): array
    {
        $rows = DB::table('buildings')->where('is_active', 1)->orderBy('phase')->orderBy('row')->orderBy('column')->get();
        $names = $rows->pluck('name', 'id');
        $ccId = BuildingId::CommandCenter->value;

        return $rows->map(function ($b) use ($names, $ccId) {
            $camel = preg_replace('/^building_/', '', $b->name);
            $id = (int) $b->id;
            $maxLevel = (int) ($b->max_level ?? 1);

            $levels = [];
            for ($lvl = 1; $lvl <= $maxLevel; $lvl++) {
                $levels[] = [
                    'level' => $lvl,
                    'ap' => $this->buildingCosts->levelupAp($id, $lvl, (int) $b->ap_for_levelup),
                    'regolith' => $this->buildingCosts->levelupRegolith($id, $lvl),
                ];
            }

            $reqId = $b->required_building_id !== null ? (int) $b->required_building_id : null;
            $reqLevel = (int) ($b->required_building_level ?? 1);

            return [
                'key' => $b->name,
                'label' => __('techtree.'.$b->name),
                'description' => __('buildings.'.$camel.'_desc'),
                'requires' => [
                    'cc_level' => $reqId === $ccId ? $reqLevel : null,
                    'building' => $reqId !== null && $reqId !== $ccId
                        ? ['label' => __('techtree.'.$names[$reqId]), 'level' => $reqLevel]
                        : null,
                ],
                'max_level' => $maxLevel,
                'supply_cost' => (int) $b->supply_cost,
                'placement_cost' => $this->buildingCosts->placementCost($id),
                'levels' => $levels,
                'is_path' => PathBuildingGateService::isPathBuilding($id),
            ];
        })->values()->all();
    }
}
