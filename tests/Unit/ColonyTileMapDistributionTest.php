<?php

namespace Tests\Unit;

use App\Services\ColonyTileService;
use Tests\TestCase;

/**
 * A44 / T20: Sol-1 map generation must be statistically sound.
 *
 * Root cause: randomizeOuterRingRows() rolled every tile with a single LCG step
 * on `$seed + $salt`. Consecutive salts shifted the roll by 1664525 mod 100 = 25,
 * so every map only ever saw the 4 rolls x, x+25, x+50, x+75 (4 map archetypes,
 * Ring 2 with exactly 3 hazards or none). For real players the seed comes from
 * random_int(1, PHP_INT_MAX): `$seed * 1664525` overflowed into float, the low
 * bits were lost and the roll was almost always 0 or 24.
 *
 * Expected weights mirror ColonyTileService::resolveTileType().
 */
class ColonyTileMapDistributionTest extends TestCase
{
    private const RING2_WEIGHTS = ['terrain_hazard' => 10, 'terrain_empty' => 90];

    private const RING3_WEIGHTS = [
        'terrain_impassable' => 5,
        'terrain_hazard' => 10,
        'regolith_poor' => 20,
        'regolith_normal' => 20,
        'regolith_rich' => 10,
        'terrain_empty' => 35,
    ];

    /** Tolerance in percentage points. */
    private const TOLERANCE = 10.0;

    /** @return list<int> */
    private function smallSeeds(): array
    {
        return range(1, 100);
    }

    /** @return list<int> seeds in the range random_int(1, PHP_INT_MAX) produces in practice */
    private function largeSeeds(): array
    {
        $seeds = [];
        for ($i = 1; $i <= 100; $i++) {
            $seeds[] = PHP_INT_MAX - $i * 92_233_720_368_547;
        }

        return $seeds;
    }

    /**
     * @param  list<int>  $seeds
     * @return array{ring2: array<string,int>, ring3: array<string,int>, ring2Hazards: list<int>, maps: list<string>}
     */
    private function sample(array $seeds): array
    {
        $service = app(ColonyTileService::class);
        $ring2 = [];
        $ring3 = [];
        $ring2Hazards = [];
        $maps = [];

        foreach ($seeds as $seed) {
            $rows = $service->randomizeOuterRingRows($seed);
            $hazards = 0;
            foreach ($rows as $row) {
                if ($row['ring'] === 2) {
                    $ring2[$row['tile_type']] = ($ring2[$row['tile_type']] ?? 0) + 1;
                    $hazards += $row['tile_type'] === 'terrain_hazard' ? 1 : 0;
                } elseif ($row['is_explored'] === 0) {
                    // The pre-explored scout tile is forced to regolith — exclude it.
                    $ring3[$row['tile_type']] = ($ring3[$row['tile_type']] ?? 0) + 1;
                }
            }
            $ring2Hazards[] = $hazards;
            $maps[] = serialize(array_map(fn (array $r): array => [$r['q'], $r['r'], $r['tile_type']], $rows));
        }

        return ['ring2' => $ring2, 'ring3' => $ring3, 'ring2Hazards' => $ring2Hazards, 'maps' => $maps];
    }

    /**
     * @param  array<string,int>  $expectedWeights
     * @param  array<string,int>  $observed
     */
    private function assertDistribution(array $expectedWeights, array $observed, string $label): void
    {
        $total = array_sum($observed);
        $this->assertGreaterThan(0, $total);
        foreach ($expectedWeights as $type => $weight) {
            $share = 100 * ($observed[$type] ?? 0) / $total;
            $this->assertEqualsWithDelta(
                $weight,
                $share,
                self::TOLERANCE,
                "{$label}: share of {$type} is ".round($share, 1)."%, expected ~{$weight}%"
            );
        }
    }

    public function test_ring_distribution_matches_weights_for_small_seeds(): void
    {
        $sample = $this->sample($this->smallSeeds());

        $this->assertDistribution(self::RING2_WEIGHTS, $sample['ring2'], 'small seeds ring 2');
        $this->assertDistribution(self::RING3_WEIGHTS, $sample['ring3'], 'small seeds ring 3');
    }

    public function test_ring_distribution_matches_weights_for_large_seeds(): void
    {
        $sample = $this->sample($this->largeSeeds());

        $this->assertDistribution(self::RING2_WEIGHTS, $sample['ring2'], 'large seeds ring 2');
        $this->assertDistribution(self::RING3_WEIGHTS, $sample['ring3'], 'large seeds ring 3');
    }

    public function test_maps_are_almost_all_distinct(): void
    {
        $sample = $this->sample(array_merge($this->smallSeeds(), $this->largeSeeds()));

        $this->assertGreaterThanOrEqual(190, count(array_unique($sample['maps'])));
    }

    /**
     * Ring-2 hazard count per map should look binomial(12, 0.1): 0, 1, 2 and
     * 3+ hazards all occur. The broken LCG only produced 0 or 3.
     */
    public function test_ring2_hazard_counts_are_not_locked_to_archetypes(): void
    {
        $sample = $this->sample(array_merge($this->smallSeeds(), $this->largeSeeds()));
        $counts = array_count_values($sample['ring2Hazards']);

        $this->assertGreaterThan(20, $counts[1] ?? 0, 'maps with exactly 1 ring-2 hazard: '.json_encode($counts));
        $this->assertGreaterThan(10, $counts[2] ?? 0, 'maps with exactly 2 ring-2 hazards: '.json_encode($counts));
    }

    /**
     * Within one map the 12 ring-2 + 9 ring-3 rolls must not collapse onto 4
     * values: a single map's ring 3 should regularly show 4+ distinct tile types.
     */
    public function test_rolls_within_a_map_are_not_limited_to_four_values(): void
    {
        $service = app(ColonyTileService::class);
        $richMaps = 0;

        foreach (array_merge($this->smallSeeds(), $this->largeSeeds()) as $seed) {
            $types = [];
            foreach ($service->randomizeOuterRingRows($seed) as $row) {
                if ($row['ring'] === 3) {
                    $types[$row['tile_type']] = true;
                }
            }
            $richMaps += count($types) >= 5 ? 1 : 0;
        }

        $this->assertGreaterThan(40, $richMaps, 'maps with 5+ distinct ring-3 tile types out of 200');
    }

    public function test_same_seed_yields_identical_map_for_small_and_large_seeds(): void
    {
        $service = app(ColonyTileService::class);

        foreach ([1, 42, 777, PHP_INT_MAX, PHP_INT_MAX - 12345] as $seed) {
            $this->assertSame($service->randomizeOuterRingRows($seed), $service->randomizeOuterRingRows($seed));
        }
    }
}
