<?php

namespace Tests\Unit\Support;

use App\Support\SeededRandom;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * A44 / T20: shared seeded RNG replacing the single-step LCG hash
 * `abs(($seed * 1664525 + 1013904223) & 0x7FFFFFFF)` that was copy-pasted into
 * ColonyTileService, BarService, MerchantService and GameTick. That hash made
 * neighbouring seeds (seed+1, seed+2, ...) strongly correlated and collapsed to
 * ~0 for seeds above ~5.5e12 (float overflow), i.e. for every real run seed
 * from random_int(1, PHP_INT_MAX).
 */
class SeededRandomTest extends TestCase
{
    public function test_int_is_deterministic_and_within_bounds(): void
    {
        foreach ([0, 1, 12345, PHP_INT_MAX, PHP_INT_MIN, -7] as $seed) {
            $first = SeededRandom::int($seed, 3, 17);
            $this->assertSame($first, SeededRandom::int($seed, 3, 17));
            $this->assertGreaterThanOrEqual(3, $first);
            $this->assertLessThanOrEqual(17, $first);
        }
    }

    public function test_int_returns_min_for_degenerate_range(): void
    {
        $this->assertSame(5, SeededRandom::int(99, 5, 5));
        $this->assertSame(5, SeededRandom::int(99, 5, 2));
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function seedBaseProvider(): array
    {
        return [
            'small base' => [1000],
            'large base' => [PHP_INT_MAX - 5_000_000],
        ];
    }

    #[DataProvider('seedBaseProvider')]
    public function test_consecutive_seeds_are_uniform_over_0_to_99(int $base): void
    {
        $buckets = array_fill(0, 10, 0);
        for ($i = 0; $i < 5000; $i++) {
            $buckets[intdiv(SeededRandom::int($base + $i, 0, 99), 10)]++;
        }

        foreach ($buckets as $decile => $count) {
            // Expected 500 per decile — generous band.
            $this->assertGreaterThan(400, $count, "decile {$decile}");
            $this->assertLessThan(600, $count, "decile {$decile}");
        }
    }

    #[DataProvider('seedBaseProvider')]
    public function test_consecutive_seeds_are_not_correlated(int $base): void
    {
        // Pairs (roll(s), roll(s+1)) over a 4x4 grid should fill every cell.
        $cells = [];
        for ($i = 0; $i < 2000; $i++) {
            $a = intdiv(SeededRandom::int($base + $i, 0, 99), 25);
            $b = intdiv(SeededRandom::int($base + $i + 1, 0, 99), 25);
            $cells["{$a}-{$b}"] = ($cells["{$a}-{$b}"] ?? 0) + 1;
        }

        $this->assertCount(16, $cells);
        foreach ($cells as $cell => $count) {
            $this->assertGreaterThan(75, $count, "cell {$cell} (expected ~125)");
        }
    }

    public function test_generator_sequence_is_reproducible(): void
    {
        $a = SeededRandom::generator(PHP_INT_MAX - 3);
        $b = SeededRandom::generator(PHP_INT_MAX - 3);

        $seqA = [];
        $seqB = [];
        for ($i = 0; $i < 20; $i++) {
            $seqA[] = $a->getInt(0, 99);
            $seqB[] = $b->getInt(0, 99);
        }

        $this->assertSame($seqA, $seqB);
        $this->assertGreaterThan(10, count(array_unique($seqA)));
    }
}
