<?php

namespace App\Http\Controllers;

use App\Enums\BuildingId;
use App\Services\BuildingCostService;
use App\Services\OnboardingHintService;
use App\Services\PathBuildingGateService;
use App\Services\Techtree\KnowledgeEffectDescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NexusDbController extends Controller
{
    public function __construct(
        private readonly OnboardingHintService $onboardingHintService,
        private readonly BuildingCostService $buildingCosts,
        private readonly KnowledgeEffectDescriptionService $knowledgeEffects,
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
                $trust = (int) config("knowledge.{$k}.trust_per_lv", 0);
                foreach ((array) config("knowledge.{$k}.levelup_costs", []) as $level => $ap) {
                    $effects = array_map(fn ($line) => $line['text'].($line['chip'] ? ' '.$line['chip']['value'] : ''), $this->knowledgeEffects->effectsAtLevel($k, (int) $level));
                    if ($trust !== 0) {
                        $effects[] = __('nexusdb.effect_trust', ['n' => ($trust > 0 ? '+' : '').$trust]);
                    }
                    $levels[] = ['level' => (int) $level, 'ap' => (int) $ap, 'effects' => $effects];
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

    /** Focus building (Fokus-Gebäude) an advisor comes with; the engineer is fixed and has none. */
    private const ADVISOR_FOCUS_BUILDING = [
        'scientist' => 'building_sciencelab',
        'pilot' => 'building_hangar',
        'trader' => 'building_bar',
    ];

    /** @return list<array<string, mixed>> */
    private function advisorCatalog(): array
    {
        return DB::table('personell')->where('is_active', 1)->orderBy('phase')->orderBy('row')->orderBy('column')->get()
            // Strategist is postponed (GDD §13.6) and may return with the Uplink building: hidden, not removed.
            ->reject(fn ($r) => $r->name === 'techs_strategist')
            ->map(function ($r) {
                $k = preg_replace('/^techs_/', '', $r->name);

                return [
                    'key' => $r->name,
                    'label' => __('advisors.'.$k),
                    'description' => __('advisors.'.$k.'_desc'),
                    'requires' => $this->requirements($r),
                    'hire_cost' => (int) config("advisors.{$k}.credits"),
                    'focus_building' => self::ADVISOR_FOCUS_BUILDING[$k] ?? null,
                    'ranks' => $this->advisorRanks($k),
                ];
            })->values()->all();
    }

    /**
     * Per rank: one-time cost (hire at rank 1, promotion above), upkeep per Sol and the bonuses valid at that rank.
     *
     * @return list<array{rank: int, cost: int, upkeep: int, bonuses: list<string>}>
     */
    private function advisorRanks(string $k): array
    {
        $ranks = [];
        foreach ((array) config('game.advisor.upkeep') as $rank => $upkeep) {
            $rank = (int) $rank;
            $bonuses = [__('nexusdb.bonus_ap', ['n' => (int) config("game.advisor.ap_per_rank.{$rank}", 0)])];
            // Field cap ("Fach-Deckel", AP scale 2026-11-13 §11.1), shown as the total per Sol.
            $capBonus = (int) config("game.advisor.cap_bonus_per_rank.{$rank}", 0);
            if ($k === 'engineer') {
                $bonuses[] = __('nexusdb.bonus_ap_cap', ['n' => (int) config('game.build.ap_cap_per_sol.base', 0) + $capBonus]);
            } elseif ($k === 'scientist') {
                $bonuses[] = __('nexusdb.bonus_ap_cap_lab', ['n' => (int) config('game.knowledge_ap_cap_per_sol.base', 0) + $capBonus]);
            } elseif ($k === 'pilot') {
                $bonuses[] = __('nexusdb.bonus_ap_cap_training', ['n' => (int) config('path_depth.training_ap_cap_per_sol.base', 0) + $capBonus]);
                $bonuses[] = __('nexusdb.bonus_mission_chance', ['n' => (int) round($rank * (float) config('game.missions.difficulty.pilot_rank_bonus_pct', 0) * 100)]);
            } elseif ($k === 'trader') {
                $bonuses[] = __('nexusdb.bonus_ship_discount', ['n' => (int) config("game.hangar.consul_ap_discount.{$rank}", 0)]);
                $bonuses[] = __('nexusdb.bonus_merchant_forecast', ['n' => (int) config("game.merchant.forecast_sols.{$rank}", 0)]);
            }
            $ranks[] = [
                'rank' => $rank,
                'cost' => $rank === 1 ? (int) config("advisors.{$k}.credits") : (int) config("game.advisor.promotion_costs.{$rank}", 0),
                'upkeep' => (int) $upkeep,
                'bonuses' => $bonuses,
            ];
        }

        return $ranks;
    }
}
