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

    /**
     * T10 (2026-09-28): buildRegolithSellOffer() is the rare generic-guest
     * Regolith->Credits sell offer. Always gives Regolith, always gets Credits,
     * deterministic per seed, and the give amount covers the same 10-30 range
     * (step 5) as buildBarterOffer().
     */
    public function test_regolith_sell_offer_always_gives_regolith_for_credits(): void
    {
        $bar = app(BarService::class);
        $build = (new \ReflectionClass($bar))->getMethod('buildRegolithSellOffer');
        $basePrices = [3 => 25, 4 => 110, 5 => 50];

        $giveAmounts = [];
        for ($seed = 1; $seed <= 500; $seed++) {
            [$giveResId, $giveAmount, $getResId, $getAmount] = $build->invoke($bar, $seed, $basePrices);

            $this->assertSame(3, $giveResId, 'give side must always be Regolith');
            $this->assertSame(1, $getResId, 'get side must always be Credits');
            $this->assertGreaterThanOrEqual(10, $giveAmount);
            $this->assertLessThanOrEqual(30, $giveAmount);
            $this->assertGreaterThan(0, $getAmount);

            $giveAmounts[$giveAmount] = true;
        }

        // give amount steps by 5 in [10, 30] -> 5 distinct values, all should occur over 500 seeds.
        $this->assertCount(5, $giveAmounts, json_encode(array_keys($giveAmounts)));
    }

    public function test_regolith_sell_offer_is_deterministic_per_seed(): void
    {
        $bar = app(BarService::class);
        $build = (new \ReflectionClass($bar))->getMethod('buildRegolithSellOffer');
        $basePrices = [3 => 25, 4 => 110, 5 => 50];

        foreach ([1, 42, 99_991] as $seed) {
            $this->assertSame(
                $build->invoke($bar, $seed, $basePrices),
                $build->invoke($bar, $seed, $basePrices)
            );
        }
    }
}
