<?php

namespace Tests\Feature\Playtest\Utility;

use App\Services\ResourcesService;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;

/**
 * Spec 2.6 (v1 heuristic; Option B replaces only this class): among the Regolith
 * steps worth doing (net > min_net), the goal is the one with the best net per
 * Regolith — dropped when the missing Regolith takes longer than
 * goal_max_wait_sols at the current income (T30 §14.4).
 *
 * Review S3 I7: affordable ("ready") steps are options too, and the goal is
 * sticky — it stays until it is bought or no longer an option, so a just
 * affordable goal is not replaced by a more expensive one and the saved
 * Regolith is not lost to it. Only a ready step with a better ratio goes first.
 */
final class HeuristicGoalProvider
{
    public static function goal(BotSession $b, BotContext $ctx): ?SavingsGoal
    {
        $options = self::options($b, $ctx);
        $best = $options[0] ?? null;
        $sticky = null;
        foreach ($options as $option) {
            if ($option->step === $b->savingsGoalStep) {
                $sticky = $option;
            }
        }
        $goal = $sticky !== null && ! ($best->ready && $best->milliRatio() > $sticky->milliRatio()) ? $sticky : $best;
        $b->savingsGoalStep = $goal?->step;

        return $goal;
    }

    /** @return list<SavingsGoal> every valid option, best net per Regolith first */
    public static function options(BotSession $b, BotContext $ctx): array
    {
        $options = [];

        $cc = Valuers::ccRow($b);
        if ($cc !== null && ($cc->max_level === null || (int) $cc->level < (int) $cc->max_level)
            && ((int) $cc->level !== 1 || BotStrategy::agrardomPlaced($b))
            && ! ($ctx->phase === 1 && (int) $cc->level >= 3)) {
            $rg = BotStrategy::levelupRegolith($cc);
            $key = 'cc:25:'.((int) $cc->level + 1);
            $options[] = new SavingsGoal('cc', $key, $rg, Valuers::cc($b, $ctx, $cc)->net(), $key, $rg <= $ctx->regolith);
        }

        $path = self::nextPathBuilding($b, $ctx);
        if ($path !== null) {
            $e = Valuers::pathPlacement($b, $ctx, $path);
            $options[] = new SavingsGoal('path', 'place:'.$path, $e->regolithCost, $e->net(), 'place:'.$path, $e->regolithCost <= $ctx->regolith);
        }

        $freeSupply = app(ResourcesService::class)->getFreeSupply($b->colonyId);
        foreach (Valuers::upgradeRows($b) as $row) {
            $rg = BotStrategy::levelupRegolith($row);
            $supplyBlocked = ! ResourcesService::reservesFirstLevel($row) && BotStrategy::supplyGated($row) && $freeSupply < (int) $row->supply_cost;
            if (! $supplyBlocked) {
                $key = 'invest:'.$row->building_id.':'.$row->instance_id;
                $options[] = new SavingsGoal('upgrade', $key, $rg, Valuers::upgrade($b, $ctx, $row)->net(), $key.'>'.((int) $row->level + 1), $rg <= $ctx->regolith);
            }
        }

        $minNet = (float) $ctx->weights['min_net'];
        $maxWait = (float) $ctx->weights['goal_max_wait_sols'];
        $options = array_values(array_filter($options, fn (SavingsGoal $g): bool => $g->regolithCost > 0 && $g->net > $minNet
            && ($g->regolithCost - $ctx->regolith) / max(1.0, $ctx->incomeRg) <= $maxWait));
        usort($options, fn (SavingsGoal $x, SavingsGoal $y): int => [-$x->milliRatio(), $x->key] <=> [-$y->milliRatio(), $y->key]);

        return $options;
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
