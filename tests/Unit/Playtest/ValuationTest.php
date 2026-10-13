<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\Utility\Valuation;

/** Spec 2.1/2.2/2.4: discount sum, streams, horizon, scarcity, lambda EMA, urgency. */
class ValuationTest extends TestCase
{
    public function test_discount_sum_matches_the_spec_example(): void
    {
        $this->assertEqualsWithDelta(16.558, Valuation::discountSum(0.95, 40), 0.01);
        $this->assertSame(0.0, Valuation::discountSum(0.95, 0));
    }

    public function test_discount_sum_without_discounting_is_the_horizon(): void
    {
        $this->assertSame(12.0, Valuation::discountSum(1.0, 12));
    }

    public function test_stream_discounts_latency_and_shortens_the_horizon(): void
    {
        $this->assertEqualsWithDelta(44.12, Valuation::stream(3.0, 2, 0.95, 40), 0.01);
        $this->assertSame(0.0, Valuation::stream(3.0, 5, 0.95, 5));
    }

    public function test_horizon_is_capped_and_never_negative(): void
    {
        $this->assertSame(40, Valuation::horizon(10, 100, 40));
        $this->assertSame(10, Valuation::horizon(90, 100, 40));
        $this->assertSame(0, Valuation::horizon(105, 100, 40));
    }

    public function test_scarcity_is_demand_over_stock_clamped(): void
    {
        $this->assertSame(3.0, Valuation::scarcity(30, 10, 0.5, 4.0));
        $this->assertSame(4.0, Valuation::scarcity(100, 0, 0.5, 4.0));
        $this->assertSame(0.5, Valuation::scarcity(1, 100, 0.5, 4.0));
    }

    public function test_lambda_ema_and_urgency(): void
    {
        $this->assertEqualsWithDelta(0.95, Valuation::lambdaEma(0.5, 2.0, 0.3), 1e-9);
        $this->assertSame(1.0, Valuation::urgency(15, 15, 0.2));
        $this->assertEqualsWithDelta(1.6, Valuation::urgency(18, 15, 0.2), 1e-9);
    }
}
