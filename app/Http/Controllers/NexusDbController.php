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
            'knowledge' => $this->knowledgeCatalog(),
            'ships' => $this->shipCatalog(),
            'advisors' => $this->advisorCatalog(),
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

    /** @return array<int, string> building id => name */
    private function buildingNames(): array
    {
        return DB::table('buildings')->pluck('name', 'id')->all();
    }

    /**
     * Building requirements of a reference row (primary plus optional second one) as label/level pairs.
     *
     * @return list<array{label: string, level: int}>
     */
    private function requirements(object $row): array
    {
        $names = $this->buildingNames();
        $out = [];
        foreach ([['required_building_id', 'required_building_level'], ['required_building2_id', 'required_building2_level']] as [$idCol, $lvlCol]) {
            $id = $row->{$idCol} ?? null;
            if ($id !== null && isset($names[$id])) {
                $out[] = ['label' => __('techtree.'.$names[$id]), 'level' => (int) ($row->{$lvlCol} ?? 1)];
            }
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function knowledgeCatalog(): array
    {
        return DB::table('researches')->where('is_active', 1)->orderBy('phase')->orderBy('row')->orderBy('column')->get()
            ->map(function ($r) {
                $k = preg_replace('/^knowledge_/', '', $r->name);
                $levels = [];
                foreach ((array) config("knowledge.{$k}.levelup_costs", []) as $level => $ap) {
                    $levels[] = ['level' => (int) $level, 'ap' => (int) $ap];
                }

                return [
                    'key' => $r->name,
                    'label' => __('techtree.'.$r->name),
                    'description' => __('knowledge.'.$k.'_desc'),
                    'requires' => $this->requirements($r),
                    'max_level' => count($levels),
                    'levels' => $levels,
                ];
            })->values()->all();
    }

    /** @return list<array<string, mixed>> */
    private function shipCatalog(): array
    {
        return DB::table('ships')->where('is_active', 1)->orderBy('phase')->orderBy('row')->orderBy('column')->get()
            ->map(function ($r) {
                $k = preg_replace('/^ship_/', '', $r->name);

                return [
                    'key' => $r->name,
                    'label' => __('ships.'.$k),
                    'description' => __('ships.'.$k.'_desc'),
                    'requires' => $this->requirements($r),
                    'nexus_cost' => (int) config("ships.{$k}.nexus_cost"),
                    'delivery_sols' => (int) config("ships.{$k}.nexus_delivery_ticks"),
                ];
            })->values()->all();
    }

    /** @return list<array<string, mixed>> */
    private function advisorCatalog(): array
    {
        return DB::table('personell')->where('is_active', 1)->orderBy('phase')->orderBy('row')->orderBy('column')->get()
            ->map(function ($r) {
                $k = preg_replace('/^techs_/', '', $r->name);

                return [
                    'key' => $r->name,
                    'label' => __('advisors.'.$k),
                    'description' => __('advisors.'.$k.'_desc'),
                    'requires' => $this->requirements($r),
                    'hire_cost' => (int) config("advisors.{$k}.credits"),
                ];
            })->values()->all();
    }
}
