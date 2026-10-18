<?php

namespace Tests\Feature\Playtest\Utility\Sources;

use App\Enums\BuildingId;
use App\Services\ResourcesService;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;
use Tests\Feature\Playtest\Utility\HeuristicGoalProvider;
use Tests\Feature\Playtest\Utility\Valuers;

/**
 * S3 (T30 §14 U1-U3): every level-up of a placed building up to max_level — no
 * Lv2 cap, no building-id tie-break, no path buffer. Regolith-blocked steps are
 * the goal provider's job; supply-blocked steps lend their value to housing.
 * Replaces invest_production and focus_engineering_levelup in the utility engine.
 */
final class UpgradeSource implements CandidateSource
{
    public function __construct(private readonly int $band) {}

    public function name(): string
    {
        return 'invest_production';
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        if ($ctx->apAvailable < 1) {
            return [];
        }

        $freeSupply = app(ResourcesService::class)->getFreeSupply($b->colonyId);
        $open = [];
        $blockedBest = 0.0;
        foreach (Valuers::upgradeRows($b) as $row) {
            if (! BotStrategy::phase1Allows($b, (int) $row->building_id, (int) $row->level + 1)) {
                continue;
            }
            $regolith = BotStrategy::levelupRegolith($row);
            if ($regolith > $ctx->regolith) {
                continue;
            }
            $key = 'invest:'.$row->building_id.':'.$row->instance_id;
            $estimate = Valuers::upgrade($b, $ctx, $row);
            $opportunity = $ctx->opportunityCost($regolith, $key);
            if (! ResourcesService::reservesFirstLevel($row) && BotStrategy::supplyGated($row) && $freeSupply < (int) $row->supply_cost) {
                $blockedBest = max($blockedBest, $estimate->net() - $opportunity);

                continue;
            }
            $open[] = [$row, $key, $estimate, $opportunity];
        }

        // Review S3 I4: the next path placement waiting on supply lends housing its value too;
        // while it is the goal, housing is the goal's chain and pays no opportunity cost (spec 2.6).
        $path = HeuristicGoalProvider::nextPathBuilding($b, $ctx);
        $goalChain = false;
        if ($path !== null && HeuristicGoalProvider::pathSupplyBlocked($path, $freeSupply)) {
            $blockedBest = max($blockedBest, Valuers::pathPlacement($b, $ctx, $path)->net());
            $goalChain = $ctx->goal?->key === 'place:'.$path;
        }

        // The lowest housing level gets the enabling value (spec 2.3 Wohnhabitat).
        usort($open, fn (array $x, array $y): int => [(int) $x[0]->level, $x[1]] <=> [(int) $y[0]->level, $y[1]]);
        $enabled = false;
        $candidates = [];
        foreach ($open as [$row, $key, $estimate, $opportunity]) {
            if (! $enabled && $blockedBest > 0.0 && (int) $row->building_id === BuildingId::Housing->value) {
                $estimate = $estimate->withTerm('enable', $blockedBest);
                $enabled = true;
                if ($goalChain) {
                    $opportunity = 0.0;
                }
            }
            if ($opportunity > 0.0) {
                $estimate = $estimate->withTerm('opportunity', -$opportunity);
            }
            $buildingId = (int) $row->building_id;
            $instanceId = (int) $row->instance_id;
            $candidates[] = new Candidate(
                name: 'invest_production',
                key: $key,
                band: $this->band,
                net: $estimate->net(),
                apCost: $estimate->apCost,
                do: fn (BotSession $s): array => $s->act('invest_production', 'POST', '/colony/building/invest', [
                    'building_id' => $buildingId,
                    'instance_id' => $instanceId,
                ]),
                terms: $estimate->terms,
                regolithCost: $estimate->regolithCost,
                cycleAp: $estimate->cycleAp,
            );
        }

        return $candidates;
    }
}
