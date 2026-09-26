<?php

namespace Tests\Unit;

use App\Services\BarService;
use Tests\TestCase;

/**
 * A44 / T20: BarService::pseudoRand() used the same single-step LCG hash as the
 * map generator. Offer terms roll from $seed+1, $seed+2, ... — neighbouring
 * seeds shift by a fixed offset, so e.g. Corvan's buy amount (seed+2, 1..5)
 * was only ever 10 or 40 units.
 */
class BarRngDistributionTest extends TestCase
{
    private const RICH_CREDITS = 1_000_000;

    public function test_corvan_buy_offer_covers_all_resource_amount_combinations(): void
    {
        $bar = app(BarService::class);
        $combos = [];

        for ($seed = 1; $seed <= 1500; $seed++) {
            [, , $getResId, $getAmount] = $bar->buildCorvanBuyOffer($seed, 0, self::RICH_CREDITS);
            $key = "{$getResId}-{$getAmount}";
            $combos[$key] = ($combos[$key] ?? 0) + 1;
        }

        // 3 tradeable resources x 5 amounts (10..50) = 15 combos, ~100 each.
        $this->assertCount(15, $combos, json_encode($combos));
        foreach ($combos as $key => $count) {
            $this->assertGreaterThan(50, $count, "combo {$key}");
        }
    }

    public function test_barter_offer_covers_all_give_amounts_per_resource(): void
    {
        $bar = app(BarService::class);
        $build = (new \ReflectionClass($bar))->getMethod('buildBarterOffer');
        $combos = [];

        for ($seed = 1; $seed <= 1500; $seed++) {
            [$giveResId, $giveAmount] = $build->invoke($bar, $seed, [3 => 30, 4 => 60, 5 => 50]);
            $key = "{$giveResId}-{$giveAmount}";
            $combos[$key] = ($combos[$key] ?? 0) + 1;
        }

        // 3 give resources x 5 give amounts (10..30 step 5) = 15 combos.
        $this->assertCount(15, $combos, json_encode($combos));
        foreach ($combos as $key => $count) {
            $this->assertGreaterThan(50, $count, "combo {$key}");
        }
    }

    public function test_corvan_buy_offer_is_deterministic_per_seed(): void
    {
        $bar = app(BarService::class);

        foreach ([1, 42, 99_991] as $seed) {
            $this->assertSame(
                $bar->buildCorvanBuyOffer($seed, 3, self::RICH_CREDITS),
                $bar->buildCorvanBuyOffer($seed, 3, self::RICH_CREDITS)
            );
        }
    }
}
