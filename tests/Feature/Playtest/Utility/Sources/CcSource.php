<?php

namespace Tests\Feature\Playtest\Utility\Sources;

use App\Enums\BuildingId;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;
use Tests\Feature\Playtest\Utility\Valuers;

/** S3 (T30 §14 U4): the next CC level, offered only when affordable; otherwise HeuristicGoalProvider saves for it. */
final class CcSource implements CandidateSource
{
    public function __construct(private readonly int $band) {}

    public function name(): string
    {
        return 'invest_cc';
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        $cc = Valuers::ccRow($b);
        if ($cc === null || $ctx->apAvailable < 1 || BotStrategy::apCapped($b, BuildingId::CommandCenter->value, 1)) {
            return [];
        }
        $level = (int) $cc->level;
        if (($cc->max_level !== null && $level >= (int) $cc->max_level)
            || ($level === 1 && ! BotStrategy::agrardomPlaced($b))
            || ($ctx->phase === 1 && $level >= 3)
            || ! BotStrategy::phase1Allows($b, BuildingId::CommandCenter->value, $level + 1)) {
            return [];
        }
        $regolith = BotStrategy::levelupRegolith($cc);
        if ($regolith > $ctx->regolith) {
            return [];
        }

        $key = 'cc:25:'.($level + 1);
        $estimate = Valuers::cc($b, $ctx, $cc);
        $opportunity = $ctx->opportunityCost($regolith, $key);
        if ($opportunity > 0.0) {
            $estimate = $estimate->withTerm('opportunity', -$opportunity);
        }

        return [new Candidate(
            name: 'invest_cc',
            key: $key,
            band: $this->band,
            net: $estimate->net(),
            apCost: $estimate->apCost,
            do: fn (BotSession $s): array => $s->act('invest_cc', 'POST', '/colony/building/invest', [
                'building_id' => BuildingId::CommandCenter->value,
            ]),
            terms: $estimate->terms,
            regolithCost: $estimate->regolithCost,
            cycleAp: $estimate->cycleAp,
        )];
    }
}
