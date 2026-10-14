<?php

namespace Tests\Feature\Playtest\Utility\Sources;

use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;
use Tests\Feature\Playtest\Utility\Valuers;

/** S3 (T30 §14 U6): every researchable knowledge, valued by its next level's effect per AP. */
final class ResearchSource implements CandidateSource
{
    public function __construct(private readonly int $band) {}

    public function name(): string
    {
        return 'research_knowledge';
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

        $candidates = [];
        foreach (BotStrategy::researchableKnowledge($b)['eligible'] as $knowledge) {
            $id = $knowledge->id;
            $estimate = Valuers::research($b, $ctx, $id, $knowledge->level, $knowledge->ap_spend);
            $candidates[] = new Candidate(
                name: 'research_knowledge',
                key: 'research:'.$id,
                band: $this->band,
                net: $estimate->net(),
                apCost: $estimate->apCost,
                // Same request as the legacy rule: levelup only once it can succeed.
                do: fn (BotSession $s): array => BotStrategy::researchLevelupReady($s, $id)
                    ? $s->act('research_knowledge', 'POST', "/techtree/research/{$id}/order", ['order' => 'levelup'])
                    : $s->act('research_knowledge', 'POST', "/techtree/research/{$id}/order", ['order' => 'add', 'ap' => 1]),
                terms: $estimate->terms,
                cycleAp: $estimate->cycleAp,
            );
        }

        return $candidates;
    }
}
