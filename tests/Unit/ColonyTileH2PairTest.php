<?php

namespace Tests\Unit;

use App\Services\ColonyTileService;
use Tests\TestCase;

/**
 * A44/H2: the two guaranteed pre-explored Ring-3 tiles ("Rush"/"Steady") must
 * always form a real Pareto-contrast pair (one strictly higher yield tier AND
 * strictly lower deposit tier than the other — neither combination dominates
 * the other on both axes), and the identity of the pair must vary across
 * runs/seeds rather than always being the same two tile_types (Owner decision
 * 2026-09-27, see docs/superpowers/plans/2026-09-27-a44-h1-h2-vorkommen-spec.md §6.3).
 */
class ColonyTileH2PairTest extends TestCase
{
    private const YIELD_TIER = [
        'regolith_y1_d1' => 1, 'regolith_y1_d2' => 1, 'regolith_y1_d3' => 1,
        'regolith_y2_d1' => 2, 'regolith_y2_d2' => 2, 'regolith_y2_d3' => 2,
        'regolith_y3_d1' => 3, 'regolith_y3_d2' => 3,
    ];

    private const DEPOSIT_TIER = [
        'regolith_y1_d1' => 1, 'regolith_y1_d2' => 2, 'regolith_y1_d3' => 3,
        'regolith_y2_d1' => 1, 'regolith_y2_d2' => 2, 'regolith_y2_d3' => 3,
        'regolith_y3_d1' => 1, 'regolith_y3_d2' => 2,
    ];

    /**
     * The two tile_types among the returned rows that are is_explored=1 —
     * H2's guaranteed known-vorkommen pair.
     *
     * @return array{0:string,1:string}
     */
    private function exploredPair(int $seed): array
    {
        $service = app(ColonyTileService::class);
        $rows = $service->randomizeOuterRingRows($seed);

        $explored = array_values(array_filter(
            $rows,
            fn (array $r) => $r['ring'] === 3 && $r['is_explored'] === 1
        ));

        $this->assertCount(2, $explored, "seed {$seed} must expose exactly 2 pre-explored Ring-3 tiles");

        return [$explored[0]['tile_type'], $explored[1]['tile_type']];
    }

    public function test_explored_pair_is_always_a_pareto_contrast(): void
    {
        foreach (range(1, 200) as $seed) {
            [$a, $b] = $this->exploredPair($seed);

            $this->assertArrayHasKey($a, self::YIELD_TIER, "seed {$seed}: {$a} is not a valid regolith combo");
            $this->assertArrayHasKey($b, self::YIELD_TIER, "seed {$seed}: {$b} is not a valid regolith combo");

            $yieldCmp = self::YIELD_TIER[$a] <=> self::YIELD_TIER[$b];
            $depositCmp = self::DEPOSIT_TIER[$a] <=> self::DEPOSIT_TIER[$b];

            $this->assertNotSame(0, $yieldCmp, "seed {$seed}: {$a}/{$b} have the same yield tier — not a contrast pair");
            $this->assertNotSame(0, $depositCmp, "seed {$seed}: {$a}/{$b} have the same deposit tier — not a contrast pair");
            $this->assertNotSame(
                $yieldCmp,
                $depositCmp,
                "seed {$seed}: {$a}/{$b} — one combo dominates the other on both axes, not a real tradeoff"
            );
        }
    }

    public function test_pair_identity_varies_across_seeds(): void
    {
        $pairs = [];
        foreach (range(1, 200) as $seed) {
            $pair = $this->exploredPair($seed);
            sort($pair);
            $pairs[implode('/', $pair)] = true;
        }

        $this->assertGreaterThan(1, count($pairs), 'the H2 pair must not always be the same two tile_types across seeds');
    }
}
