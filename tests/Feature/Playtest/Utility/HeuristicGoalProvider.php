<?php

namespace Tests\Feature\Playtest\Utility;

use App\Services\ResourcesService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;

/**
 * Spec 2.6 (v1 heuristic; Option B replaces only this class): among the steps
 * that fail on Regolith alone and are worth doing (net > min_net), the goal is
 * the one with the best net per Regolith — dropped when the missing Regolith
 * takes longer than goal_max_wait_sols at the current income (T30 §14.4).
 */
final class HeuristicGoalProvider
{
    public static function goal(BotSession $b, BotContext $ctx): ?SavingsGoal
    {
        $options = [];

        $cc = Valuers::ccRow($b);
        if ($cc !== null && ($cc->max_level === null || (int) $cc->level < (int) $cc->max_level)
            && ((int) $cc->level !== 1 || BotStrategy::agrardomPlaced($b))
            && ! ($ctx->phase === 1 && (int) $cc->level >= 3)) {
            $rg = BotStrategy::levelupRegolith($cc);
            if ($rg > $ctx->regolith) {
                $options[] = new SavingsGoal('cc', 'cc:25:'.((int) $cc->level + 1), $rg, Valuers::cc($b, $ctx, $cc)->net());
            }
        }

        $path = self::nextPathBuilding($b, $ctx);
        if ($path !== null) {
            $e = Valuers::pathPlacement($b, $ctx, $path);
            if ($e->regolithCost > $ctx->regolith) {
                $options[] = new SavingsGoal('path', 'place:'.$path, $e->regolithCost, $e->net());
            }
        }

        $freeSupply = app(ResourcesService::class)->getFreeSupply($b->colonyId);
        foreach (Valuers::upgradeRows($b) as $row) {
            $rg = BotStrategy::levelupRegolith($row);
            $supplyBlocked = ! ResourcesService::reservesFirstLevel($row) && BotStrategy::supplyGated($row) && $freeSupply < (int) $row->supply_cost;
            if ($rg > $ctx->regolith && ! $supplyBlocked) {
                $options[] = new SavingsGoal('upgrade', 'invest:'.$row->building_id.':'.$row->instance_id, $rg, Valuers::upgrade($b, $ctx, $row)->net());
            }
        }

        $minNet = (float) $ctx->weights['min_net'];
        $maxWait = (float) $ctx->weights['goal_max_wait_sols'];
        $options = array_values(array_filter($options, fn (SavingsGoal $g): bool => $g->net > $minNet
            && ($g->regolithCost - $ctx->regolith) / max(1.0, $ctx->incomeRg) <= $maxWait));
        usort($options, fn (SavingsGoal $x, SavingsGoal $y): int => [-(int) round($x->ratio() * 1000), $x->key] <=> [-(int) round($y->ratio() * 1000), $y->key]);

        return $options[0] ?? null;
    }

    /** Next unplaced path building of the opening's order, if the CC allows one more (placed < CC level − 1) and the Agrardom stands. */
    private static function nextPathBuilding(BotSession $b, BotContext $ctx): ?int
    {
        if (! BotStrategy::agrardomPlaced($b)) {
            return null;
        }
        $placed = DB::table('colony_buildings')->where('colony_id', $b->colonyId)->whereIn('building_id', Valuers::PATH_IDS)
            ->whereNotNull('tile_x')->orderBy('building_id')->pluck('building_id')->map(fn ($id): int => (int) $id)->all();
        if (count($placed) >= BotStrategy::ccLevel($b) - 1) {
            return null;
        }
        foreach (BotStrategy::pathBuildingOrder($ctx->opening) as $id) {
            if (! in_array($id, $placed, true)) {
                return $id;
            }
        }

        return null;
    }
}
