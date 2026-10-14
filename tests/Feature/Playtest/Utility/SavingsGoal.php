<?php

namespace Tests\Feature\Playtest\Utility;

/** Spec 2.6: the one Regolith target the bot is saving for. */
final readonly class SavingsGoal
{
    /**
     * @param  string  $step  identity of the concrete step (key + target level) — what stickiness compares
     * @param  bool  $ready  affordable now: kept as the goal until bought (review S3 I7)
     */
    public function __construct(
        public string $kind,
        public string $key,
        public int $regolithCost,
        public float $net,
        public string $step = '',
        public bool $ready = false,
    ) {}

    public function ratio(): float
    {
        return $this->net / max(1, $this->regolithCost);
    }

    /** Ratio in milli units, the compared value (spec 2.10). */
    public function milliRatio(): int
    {
        return (int) round($this->ratio() * 1000);
    }

    /** Building id of a path placement goal, null for every other kind. */
    public function placementBuildingId(): ?int
    {
        return $this->kind === 'path' ? (int) substr($this->key, strlen('place:')) : null;
    }
}
