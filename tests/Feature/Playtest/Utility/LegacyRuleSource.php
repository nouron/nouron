<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotSession;

/**
 * Spec S0: one legacy rule as a candidate source. `when` is called exactly as the
 * old loop called it; its truthy result becomes one unscored candidate whose
 * `do` runs the rule's `do` with that result.
 */
final class LegacyRuleSource implements CandidateSource
{
    /** @param array{name:string, when:callable, do:callable} $rule */
    public function __construct(
        private readonly array $rule,
        private readonly int $band,
        public readonly bool $mandatory = false,
    ) {}

    public function name(): string
    {
        return $this->rule['name'];
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        $payload = ($this->rule['when'])($b);
        if (! $payload) {
            return [];
        }
        $do = $this->rule['do'];

        return [new Candidate(
            name: $this->rule['name'],
            key: $this->rule['name'],
            band: $this->band,
            net: null,
            apCost: 0,
            do: fn (BotSession $s): array => $do($s, $payload),
            mandatory: $this->mandatory,
            payload: $payload,
        )];
    }
}
