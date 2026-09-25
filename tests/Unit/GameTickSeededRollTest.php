<?php

namespace Tests\Unit;

use App\Console\Commands\GameTick;
use Tests\TestCase;

/**
 * A44 / T20: GameTick::seededRoll() drives encounter chances (storm, instability,
 * plague) and hangar mission success from runs.rng_seed. Real runs get
 * rng_seed = random_int(1, PHP_INT_MAX); the former LCG hash overflowed into
 * float for such seeds and returned ~0 more than half the time — encounters
 * fired and missions succeeded far above their configured chance.
 */
class GameTickSeededRollTest extends TestCase
{
    private function roll(int $seed): float
    {
        $tick = app(GameTick::class);
        $method = (new \ReflectionClass($tick))->getMethod('seededRoll');

        return $method->invoke($tick, $seed, 0, 9999) / 10000;
    }

    public function test_encounter_rolls_are_uniform_for_real_run_seeds(): void
    {
        // Same seed composition as GameTick::rollStorm() for colony 1.
        $rngSeed = PHP_INT_MAX - 1_000_000_000;
        $below5 = 0;
        $below50 = 0;
        for ($tick = 1; $tick <= 2000; $tick++) {
            $roll = $this->roll($rngSeed + 1 * 7919 + $tick * 104729);
            $below5 += $roll < 0.05 ? 1 : 0;
            $below50 += $roll < 0.50 ? 1 : 0;
        }

        $this->assertEqualsWithDelta(100, $below5, 40, 'rolls < 0.05 out of 2000 (expected ~100)');
        $this->assertEqualsWithDelta(1000, $below50, 100, 'rolls < 0.50 out of 2000 (expected ~1000)');
    }

    public function test_roll_is_deterministic_per_seed(): void
    {
        foreach ([7, PHP_INT_MAX - 5] as $seed) {
            $this->assertSame($this->roll($seed), $this->roll($seed));
        }
    }
}
