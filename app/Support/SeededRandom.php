<?php

namespace App\Support;

use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Seeded, reproducible randomness for game rolls (maps, bar, merchant, tick).
 *
 * Xoshiro256** expands an int seed via SplitMix64, so the full 64-bit seed
 * (e.g. runs.rng_seed from random_int(1, PHP_INT_MAX)) is used without
 * overflow, and neighbouring seeds (seed, seed+1, ...) yield uncorrelated
 * values. Replaces the former single-step LCG hash
 * `($seed * 1664525 + 1013904223) & 0x7FFFFFFF` (A44/T20).
 */
final class SeededRandom
{
    /** Stateless roll in [min, max] for one seed. Returns $min if $max <= $min. */
    public static function int(int $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }

        return self::generator($seed)->getInt($min, $max);
    }

    /** Stateful generator for a sequence of rolls drawn in a fixed order. */
    public static function generator(int $seed): Randomizer
    {
        return new Randomizer(new Xoshiro256StarStar($seed));
    }
}
