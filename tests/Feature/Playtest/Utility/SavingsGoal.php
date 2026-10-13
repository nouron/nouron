<?php

namespace Tests\Feature\Playtest\Utility;

/** Spec 2.6: the one Regolith target the bot is saving for. */
final readonly class SavingsGoal
{
    public function __construct(
        public string $kind,
        public string $key,
        public int $regolithCost,
        public float $net,
    ) {}

    public function ratio(): float
    {
        return $this->net / max(1, $this->regolithCost);
    }
}
