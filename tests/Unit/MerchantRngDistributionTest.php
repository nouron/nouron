<?php

namespace Tests\Unit;

use App\Services\MerchantService;
use Tests\TestCase;

/**
 * A44 / T20: MerchantService used the same single-step LCG hash as the map
 * generator for its visit interval, lot sizes and item picks. Now routed
 * through SeededRandom — rolls must stay deterministic and cover their range.
 */
class MerchantRngDistributionTest extends TestCase
{
    private function invoke(string $method, mixed ...$args): mixed
    {
        $merchant = app(MerchantService::class);

        return (new \ReflectionClass($merchant))->getMethod($method)->invoke($merchant, ...$args);
    }

    public function test_visit_interval_covers_range_across_consecutive_visits(): void
    {
        $counts = [];
        for ($anchor = -1; $anchor < 600; $anchor++) {
            $target = $this->invoke('deterministicTarget', 1, $anchor, 5, 10);
            $counts[$target] = ($counts[$target] ?? 0) + 1;
        }

        ksort($counts);
        $this->assertSame([5, 6, 7, 8, 9, 10], array_keys($counts));
        foreach ($counts as $value => $count) {
            $this->assertGreaterThan(60, $count, "interval {$value} (expected ~100)");
        }
    }

    public function test_item_picks_pair_up_freely(): void
    {
        $pool = ['a', 'b', 'c', 'd'];
        $pairs = [];
        for ($tick = 1; $tick <= 1200; $tick++) {
            $picked = $this->invoke('pickItems', $pool, 2, 1, $tick);
            $pairs[implode('', $picked)] = true;
        }

        // 4 x 3 ordered pairs.
        $this->assertCount(12, $pairs);
    }

    public function test_rolls_are_deterministic(): void
    {
        $this->assertSame(
            $this->invoke('deterministicTarget', 3, 17, 5, 30),
            $this->invoke('deterministicTarget', 3, 17, 5, 30)
        );
        $this->assertSame(
            $this->invoke('pickItems', ['a', 'b', 'c', 'd'], 3, 2, 40),
            $this->invoke('pickItems', ['a', 'b', 'c', 'd'], 3, 2, 40)
        );
    }
}
