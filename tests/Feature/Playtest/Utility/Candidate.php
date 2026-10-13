<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotSession;

/**
 * One possible bot action (spec 3.1). `net` is the utility in Rg-equivalents
 * after costs, or null for an unscored legacy candidate, which is ordered by its
 * band only. `band` is the legacy rule index the candidate stands in for.
 */
final readonly class Candidate
{
    /**
     * @param  array<string, float>  $terms  breakdown of `net` (log/report only)
     * @param  \Closure(BotSession): array  $do
     */
    public function __construct(
        public string $name,
        public string $key,
        public int $band,
        public ?float $net,
        public int $apCost,
        public \Closure $do,
        public array $terms = [],
        public bool $mandatory = false,
        public mixed $payload = null,
        public int $regolithCost = 0,
    ) {}

    public function isScored(): bool
    {
        return $this->net !== null;
    }

    /** The same candidate, scored by $e (shadow mode). */
    public function withEstimate(Estimate $e): self
    {
        return new self($this->name, $this->key, $this->band, $e->net(), $e->apCost, $this->do, $e->terms, $this->mandatory, $this->payload, $e->regolithCost);
    }

    /** Utility per AP in milli-Rg-equivalents (spec 2.10); 0 for unscored candidates. */
    public function milliScore(): int
    {
        if ($this->net === null || ! is_finite($this->net)) {
            return 0;
        }

        return (int) max(-PHP_INT_MAX / 2, min(PHP_INT_MAX / 2, round($this->net / max(1, $this->apCost) * 1000)));
    }
}
