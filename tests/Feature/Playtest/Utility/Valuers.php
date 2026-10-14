<?php

namespace Tests\Feature\Playtest\Utility;

use App\Console\Commands\GameTick;
use App\Enums\BuildingId;
use App\Services\BuildingCostService;
use App\Services\ColonyTileService;
use App\Services\ResourcesService;
use App\Services\Techtree\ResearchService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;

/**
 * Utility estimates for the candidate classes of step S3 (spec 2.3 + P0), shared
 * by the shadow mode (S2) and the utility sources (S3). Only player-visible
 * state: finds count once deep-scanned (spec 2.7). Every weight is an assumption
 * (Utility/weights.php).
 */
final class Valuers
{
    public const PATH_IDS = [31, 44, 52];

    private const BIO_FACILITY = 41;

    private const RES_ORGANICS = 5;

    private const CONSTRUCTION = 90;

    private const CARTOGRAPHY = 91;

    private const GEOLOGY = 92;

    private const AGRONOMY = 93;

    public static function upgrade(BotSession $b, BotContext $ctx, object $row): Estimate
    {
        $w = $ctx->weights;
        $id = (int) $row->building_id;
        $target = (int) $row->level + 1;
        $regolith = BotStrategy::levelupRegolith($row);
        $cycleAp = BotStrategy::effectiveApForLevelup($b, (int) ($row->ap_for_levelup ?? 10));
        $ap = max(1, $cycleAp - (int) $row->ap_spend);
        $isPath = in_array($id, self::PATH_IDS, true);

        $terms = ['cost_rg' => -1.0 * $regolith];
        if ($ctx->phase === 1 && (($target === 2 && $ctx->nonCcLevel2 < 2) || ($target === 1 && $isPath && $ctx->advisors < 3))) {
            $terms['phase1'] = (float) $w['w_phase1'] * $ctx->phase1Urgency();
        }
        if ($isPath && $target >= 2) {
            $terms['tier'] = (float) $w['w_tier'];
        }
        if ($isPath && $target === 1) {
            $terms['advisor_slot'] = self::advisorSlotValue($ctx);
        }
        if ($id === $ctx->openingBuildingId && $ctx->sol <= (int) $w['opening_bias_until_sol']) {
            $terms['opening'] = (float) $w['opening_bias'];
        }
        $trust = (int) (collect(config('buildings'))->firstWhere('id', $id)['trust_per_lv'] ?? 0);
        if ($trust > 0) {
            $terms['trust'] = $trust * $ctx->price('trust');
        }
        $unlock = self::unlock($b, $ctx, $id, $target);
        if ($unlock !== 0.0) {
            $terms['unlock'] = $unlock;
        }
        if ($id === self::BIO_FACILITY) {
            $curve = config('game.production_curve.'.self::BIO_FACILITY.'.'.self::RES_ORGANICS, []);
            $food = min((int) ($curve[$target] ?? 0), self::foodDeficit($b));
            if ($food > 0) {
                $terms['food'] = $ctx->stream($food * $ctx->price('organics'), 1);
            }
        }
        $objective = $ctx->objectives['task_engineering_output'] ?? null;
        if ($objective !== null && (float) $w['w_objective'] > 0.0) {
            $terms['objective'] = (float) $w['w_objective'] / max(1, $objective['target']);
        }

        return new Estimate($terms, $ap, $regolith, $cycleAp);
    }

    public static function cc(BotSession $b, BotContext $ctx, object $cc): Estimate
    {
        $w = $ctx->weights;
        $level = (int) $cc->level;
        $target = $level + 1;
        $regolith = BotStrategy::levelupRegolith($cc);
        $cycleAp = BotStrategy::effectiveApForLevelup($b, (int) ($cc->ap_for_levelup ?? 10));
        $ap = max(1, $cycleAp - (int) $cc->ap_spend);

        $terms = ['cost_rg' => -1.0 * $regolith, 'structural' => $regolith * (float) $w['structural_offset']];
        if ($ctx->phase === 1 && $target <= 3) {
            $terms['phase1'] = (float) $w['w_phase1'] * $ctx->phase1Urgency();
        }
        if ($target >= 4) {
            $terms['tier'] = (float) $w['w_tier'];
        }
        $maxSlots = (int) config('game.advisor.max_slots', 4);
        if ($target <= $maxSlots && $ctx->advisors >= min($level, $maxSlots)) {
            $terms['advisor_slot'] = self::advisorSlotValue($ctx);
        }
        $placedPath = DB::table('colony_buildings')->where('colony_id', $b->colonyId)
            ->whereIn('building_id', self::PATH_IDS)->whereNotNull('tile_x')->count();
        if ($placedPath < count(self::PATH_IDS) && $placedPath >= $level - 1) {
            $terms['path_slot'] = (float) $w['w_tier'];
        }
        $knowledgeLevel = array_search($target, config('game.knowledge_cc_level_cap', []), true);
        if ($knowledgeLevel !== false) {
            $sum = 0.0;
            $rows = DB::table('colony_researches')->where('colony_id', $b->colonyId)
                ->whereBetween('research_id', [90, 96])->where('level', (int) $knowledgeLevel - 1)
                ->orderBy('research_id')->get(['research_id', 'level', 'ap_spend']);
            foreach ($rows as $row) {
                $sum += self::research($b, $ctx, (int) $row->research_id, (int) $row->level, (int) $row->ap_spend)->net();
            }
            if ($sum > 0.0) {
                $terms['knowledge_cap'] = $sum;
            }
        }

        return new Estimate($terms, $ap, $regolith, $cycleAp);
    }

    public static function research(BotSession $b, BotContext $ctx, int $researchId, int $level, int $apSpend): Estimate
    {
        $w = $ctx->weights;
        $target = $level + 1;
        $cfg = collect(config('knowledge'))->first(fn (array $k): bool => (int) $k['id'] === $researchId) ?? [];
        $fallback = (int) (DB::table('researches')->where('id', $researchId)->value('ap_for_levelup') ?? 0);
        $cycleAp = app(ResearchService::class)->knowledgeLevelupCost($b->colonyId, $researchId, $fallback);
        $ap = max(1, $cycleAp - $apSpend);
        $latency = (int) ceil($ap / max(1, $ctx->apInflow));

        $effect = match ($researchId) {
            self::GEOLOGY => $ctx->stream((float) (config('game.geology_harvester_bonus_per_level')[$target] ?? 0), $latency),
            self::CONSTRUCTION => $ctx->stream(($cfg['ap_cost_reduction_per_lv'][$target] ?? 0) / 100 * (float) $w['build_ap_per_sol'] * $ctx->lambdaAp, $latency),
            self::CARTOGRAPHY => $ctx->stream(($cfg['nav_ap_reduction_per_lv'][$target] ?? 0) / 100 * (float) $w['nav_ap_per_sol'] * $ctx->lambdaAp, $latency),
            // GameTick::generateAgronomyBonus() pays nothing while no Agrardom stands.
            self::AGRONOMY => self::agrardomLevel($b) === 0 ? 0.0
                : $ctx->stream(min((int) (config('game.agronomy_agrardom_bonus_per_level')[$target] ?? 0), self::foodDeficit($b)) * $ctx->price('organics'), $latency),
            default => 0.0,
        };

        $terms = ['floor' => (float) $w['research_floor']];
        if ($effect > 0.0) {
            $terms['effect'] = $effect;
        }
        $trust = (int) ($cfg['trust_per_lv'] ?? 0);
        if ($trust > 0) {
            $terms['trust'] = $trust * $ctx->price('trust');
        }

        return new Estimate($terms, $ap, 0, $cycleAp);
    }

    public static function pathPlacement(BotSession $b, BotContext $ctx, int $buildingId): Estimate
    {
        $w = $ctx->weights;
        $regolith = (int) (app(BuildingCostService::class)->placementCost($buildingId)[3] ?? 0);
        $terms = [
            'cost_rg' => -1.0 * $regolith,
            'structural' => $regolith * (float) $w['structural_offset'],
            'tier' => (float) $w['w_tier'],
            'advisor_slot' => self::advisorSlotValue($ctx),
        ];
        if ($ctx->phase === 1 && $ctx->advisors < 3) {
            $terms['phase1'] = (float) $w['w_phase1'] * $ctx->phase1Urgency();
        }
        if ($buildingId === $ctx->openingBuildingId && $ctx->sol <= (int) $w['opening_bias_until_sol']) {
            $terms['opening'] = (float) $w['opening_bias'];
        }

        return new Estimate($terms, 1, $regolith);
    }

    /** One more advisor: Rank-1 AP stream valued at λ_AP minus its upkeep (spec 2.3). */
    public static function advisorSlotValue(BotContext $ctx): float
    {
        $ap = (int) (config('game.advisor.ap_per_rank')[1] ?? 2);
        $upkeep = (int) (config('game.advisor.upkeep')[1] ?? 10);

        return $ctx->stream($ap * $ctx->lambdaAp - $upkeep * $ctx->price('credits'), 1);
    }

    /** @return list<object> placed non-CC, non-Harvester buildings below max_level */
    public static function upgradeRows(BotSession $b): array
    {
        return self::rowQuery($b)
            ->whereNotIn('cb.building_id', [BuildingId::CommandCenter->value, BuildingId::Harvester->value])
            ->whereNotNull('cb.tile_x')
            ->where(fn ($q) => $q->whereNull('bld.max_level')->orWhereColumn('cb.level', '<', 'bld.max_level'))
            ->orderBy('cb.building_id')->orderBy('cb.instance_id')
            ->get()->all();
    }

    public static function buildingRow(BotSession $b, int $buildingId, int $instanceId): ?object
    {
        return self::rowQuery($b)->where('cb.building_id', $buildingId)->where('cb.instance_id', $instanceId)->first();
    }

    public static function ccRow(BotSession $b): ?object
    {
        return self::rowQuery($b)->where('cb.building_id', BuildingId::CommandCenter->value)->addSelect('bld.max_level')->first();
    }

    private static function rowQuery(BotSession $b): Builder
    {
        return DB::table('colony_buildings as cb')
            ->join('buildings as bld', 'bld.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $b->colonyId)
            ->select('cb.building_id', 'cb.instance_id', 'cb.level', 'cb.ap_spend', 'cb.tile_x', 'bld.supply_cost', 'bld.ap_for_levelup');
    }

    private static function unlock(BotSession $b, BotContext $ctx, int $id, int $target): float
    {
        if ($id === BuildingId::Hangar->value) {
            return self::waitingFindValue($b, $ctx, $target);
        }
        if ($id === BuildingId::Sciencelab->value && $target === 2) {
            $geology = (int) (DB::table('colony_researches')->where('colony_id', $b->colonyId)->where('research_id', self::GEOLOGY)->value('level') ?? 0);
            if ($geology === 0) {
                $cfg = collect(config('knowledge'))->first(fn (array $k): bool => (int) $k['id'] === self::GEOLOGY) ?? [];
                $latency = (int) ceil((int) ($cfg['levelup_costs'][1] ?? 20) / max(1, $ctx->apInflow));

                return $ctx->stream((float) (config('game.geology_harvester_bonus_per_level')[1] ?? 0), $latency);
            }
        }

        return 0.0;
    }

    /**
     * Scanned finds waiting for exactly this hangar level (findAction needs_level /
     * ship_inactive), worth rg × yield_factor; a large one also needs a freighter.
     */
    private static function waitingFindValue(BotSession $b, BotContext $ctx, int $target): float
    {
        $service = app(ColonyTileService::class);
        $types = config('game.finds.types');
        $value = 0.0;
        $largeValue = 0.0;
        $tiles = DB::table('colony_tiles')->where('colony_id', $b->colonyId)->where('is_deep_scanned', 1)
            ->whereIn('event_type', ColonyTileService::FIND_TYPES)->orderBy('ring')->orderBy('q')->orderBy('r')->get();
        foreach ($tiles as $tile) {
            $action = $service->findAction($b->colonyId, $tile);
            if (! in_array($action['reason'], ['needs_level', 'ship_inactive'], true) || (int) ($action['needs_level'] ?? 0) > $target) {
                continue;
            }
            $worth = (int) $types[$tile->event_type]['rg'] * (float) config('game.finds.flight.yield_factor');
            if ($tile->event_type === 'find_large') {
                $largeValue += $worth;
            } else {
                $value += $worth;
            }
        }
        if ($largeValue > 0.0) {
            $hasFreighter = DB::table('colony_ships')->where('colony_id', $b->colonyId)
                ->where('ship_id', (int) config('ships.freighter.id'))->exists();
            // Option value (spec 2.2): the upgrade never forces the freighter purchase, so the bundle is >= 0.
            $value += $hasFreighter ? $largeValue
                : max(0.0, $largeValue - (int) config('ships.freighter.nexus_cost', 0) * $ctx->price('credits'));
        }

        return $value;
    }

    private static function agrardomLevel(BotSession $b): int
    {
        return (int) (DB::table('colony_buildings')->where('colony_id', $b->colonyId)->where('building_id', self::BIO_FACILITY)
            ->whereNotNull('tile_x')->orderBy('instance_id')->value('level') ?? 0);
    }

    /**
     * Organika per Sol the colony's ships fly on (review S3, P3): every ship can be
     * out on a mission or find flight, each Sol of flight costs organika_per_sol.
     */
    public static function provisionsPerSol(BotSession $b): int
    {
        return (int) config('missions.organika_per_sol', 3) * DB::table('colony_ships')->where('colony_id', $b->colonyId)->count();
    }

    /**
     * Organika the colony's need plus ship provisions exceed the current Agrardom
     * yield by (feed_colony stays mandatory). Kept as a cap on the food term: the
     * scarcity price never drops below 0.5, so uncapped surplus Organika would make
     * every Agrardom level the densest upgrade.
     */
    private static function foodDeficit(BotSession $b): int
    {
        $level = self::agrardomLevel($b);
        $curve = config('game.production_curve.'.self::BIO_FACILITY.'.'.self::RES_ORGANICS, []);

        return max(0, app(ResourcesService::class)->foodNeed($b->colonyId) + self::provisionsPerSol($b) - GameTick::cumulativeCurveYield($curve, $level));
    }
}
