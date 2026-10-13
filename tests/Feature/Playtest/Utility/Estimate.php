<?php

namespace Tests\Feature\Playtest\Utility;

/** Utility estimate of one candidate: named terms in Rg-equivalents, their sum is Net(c). */
final readonly class Estimate
{
    /** @param array<string, float> $terms */
    public function __construct(
        public array $terms,
        public int $apCost,
        public int $regolithCost,
    ) {}

    public function net(): float
    {
        return (float) array_sum($this->terms);
    }

    public function withTerm(string $name, float $value): self
    {
        return new self([...$this->terms, $name => $value], $this->apCost, $this->regolithCost);
    }
}
