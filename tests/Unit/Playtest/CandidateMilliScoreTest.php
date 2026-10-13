<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\Utility\Candidate;

/** milliScore must stay a sane int for non-finite or huge nets. */
class CandidateMilliScoreTest extends TestCase
{
    private function c(?float $net, int $ap = 2): Candidate
    {
        return new Candidate('n', 'k', 0, $net, $ap, fn () => []);
    }

    public function test_regular_and_unscored_values(): void
    {
        $this->assertSame(1500, $this->c(3.0)->milliScore());
        $this->assertSame(0, $this->c(null)->milliScore());
    }

    public function test_non_finite_and_huge_nets_are_clamped(): void
    {
        $this->assertSame(0, $this->c(INF)->milliScore());
        $this->assertSame(0, $this->c(NAN)->milliScore());
        $this->assertSame(PHP_INT_MAX / 2 | 0, $this->c(1e300)->milliScore());
    }
}
