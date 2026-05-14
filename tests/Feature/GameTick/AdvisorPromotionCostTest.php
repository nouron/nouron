<?php

namespace Tests\Feature\GameTick;

/**
 * Advisor rank-promotion cost tests.
 *
 * Tests cover the incrementAdvisorTicks() step in GameTick which charges a
 * one-time Credits cost when an advisor crosses a rank threshold.
 *
 * Config:
 *   game.advisor.rank_thresholds  = [1 => 10, 2 => 20]  (active_ticks needed to promote)
 *   game.advisor.promotion_costs  = [2 => 150, 3 => 400]  (Credits charged at target rank)
 *
 * Test fixture (from TestSeeder):
 *   Colony 1 (Springfield), user_id = 3 (Bart)
 *   Advisor id=3: user_id=3, personell_id=92 (trader), colony_id=1, rank=1, active_ticks=0
 *
 * Covered scenarios:
 *   - test_promotion_deducts_credits_from_user
 *   - test_promotion_deferred_when_insufficient_credits
 *   - test_promotion_cost_matches_config
 *   - test_no_cost_deducted_if_no_promotion
 */

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdvisorPromotionCostTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    private const USER_ID   = 3;    // Bart
    private const COLONY_ID = 1;    // Springfield

    /**
     * Trader advisor (personell_id=92) — present in test data for colony 1.
     * We use its id (3) throughout these tests.
     */
    private const ADVISOR_ID          = 3;
    private const ADVISOR_PERSONELL_ID = 92;

    /**
     * Threshold from config: advisor at rank 1 needs 10 active_ticks to become rank 2.
     * Set active_ticks to threshold - 1 so that one tick push crosses the boundary.
     */
    private const RANK1_THRESHOLD   = 10; // config('game.advisor.rank_thresholds')[1]
    private const RANK2_COST        = 150; // config('game.advisor.promotion_costs')[2]

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Remove all advisors for Bart except the trader (id=3) so upkeep costs
        // from other advisors don't contaminate the credits balance under test.
        DB::table('advisors')
            ->where('colony_id', self::COLONY_ID)
            ->where('id', '!=', self::ADVISOR_ID)
            ->delete();

        // Reset the trader advisor to rank 1 and place it one tick below the threshold.
        // After one tick increment it will reach active_ticks = threshold and be eligible.
        DB::table('advisors')
            ->where('id', self::ADVISOR_ID)
            ->update([
                'rank'        => 1,
                'active_ticks' => self::RANK1_THRESHOLD - 1,
            ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->update(['credits' => $amount]);
    }

    private function getCredits(): int
    {
        return (int) DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->value('credits');
    }

    private function getAdvisorRank(): int
    {
        return (int) DB::table('advisors')
            ->where('id', self::ADVISOR_ID)
            ->value('rank');
    }

    private function getAdvisorActiveTicks(): int
    {
        return (int) DB::table('advisors')
            ->where('id', self::ADVISOR_ID)
            ->value('active_ticks');
    }

    /**
     * Run the game:tick command at a high tick number that has no fleet orders
     * scheduled (to keep the test isolated to the advisor tick step).
     */
    private function runTick(int $tick = 9500): void
    {
        Artisan::call('game:tick', ['--tick' => $tick]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_promotion_deducts_credits_from_user(): void
    {
        // Give the player more than enough credits for the promotion cost
        $initialCredits = 10_000;
        $this->setCredits($initialCredits);

        $this->runTick(9500);

        $rank = $this->getAdvisorRank();
        $this->assertEquals(2, $rank, 'Advisor must reach rank 2 after crossing the threshold');

        $creditsAfter = $this->getCredits();
        $this->assertLessThan(
            $initialCredits,
            $creditsAfter,
            'Credits must have been deducted for the promotion'
        );
    }

    public function test_promotion_deferred_when_insufficient_credits(): void
    {
        // Exactly 0 credits — cannot afford the rank-2 promotion cost of 150
        $this->setCredits(0);

        $this->runTick(9501);

        $rank = $this->getAdvisorRank();
        $this->assertEquals(1, $rank, 'Advisor rank must stay at 1 when player cannot afford promotion cost');

        // Credits must not go negative. Passive income fires before the promotion check
        // (nexus subsidy + housing tax), so exact value depends on colony state.
        $creditsAfter = $this->getCredits();
        $this->assertGreaterThanOrEqual(0, $creditsAfter, 'Credits must not go below 0');
    }

    public function test_promotion_cost_matches_config(): void
    {
        $configCost = (int) config('game.advisor.promotion_costs.2', 150);

        // Give the player exactly enough for the configured cost plus what upkeep
        // will consume. Upkeep for rank-1 advisor = 10 Cr. Passive income also fires
        // (nexus 30 + housing × 20). We set credits high enough to isolate the promotion cost.
        // Strategy: set credits to a round number, run tick, then check the delta
        // against the expected cost (accounting for income/upkeep in the same tick).

        // Determine the net credits delta in a control run WITHOUT promotion:
        // Use a tick number where active_ticks will NOT cross the threshold.
        DB::table('advisors')
            ->where('id', self::ADVISOR_ID)
            ->update(['active_ticks' => 0]); // far from threshold

        $controlCredits = 10_000;
        $this->setCredits($controlCredits);
        $this->runTick(9510);
        $afterControl = $this->getCredits();
        $netDeltaNoPromotion = $afterControl - $controlCredits;

        // Reset advisor to one tick before promotion threshold
        DB::table('advisors')
            ->where('id', self::ADVISOR_ID)
            ->update([
                'rank'        => 1,
                'active_ticks' => self::RANK1_THRESHOLD - 1,
            ]);

        $this->setCredits($controlCredits);
        $this->runTick(9511);
        $afterPromotion = $this->getCredits();
        $netDeltaWithPromotion = $afterPromotion - $controlCredits;

        // The difference between the two runs must be exactly the promotion cost
        $promotionCostActual = $netDeltaNoPromotion - $netDeltaWithPromotion;

        $this->assertEquals(
            $configCost,
            $promotionCostActual,
            "Promotion cost must equal config value ({$configCost} Cr)"
        );
    }

    public function test_no_cost_deducted_if_no_promotion(): void
    {
        // Set active_ticks well below the threshold so no promotion fires
        DB::table('advisors')
            ->where('id', self::ADVISOR_ID)
            ->update(['active_ticks' => 0]);

        $initialCredits = 5_000;
        $this->setCredits($initialCredits);

        $this->runTick(9520);

        $rank = $this->getAdvisorRank();
        $this->assertEquals(1, $rank, 'Advisor must remain at rank 1 when threshold is not reached');

        // Credits may change due to passive income/upkeep, but must NOT decrease
        // by the promotion cost (150 Cr). The maximum possible upkeep for a single
        // rank-1 advisor is 10 Cr; passive income is >= 30 Cr (nexus subsidy alone).
        // Net: credits should be >= initial (income > upkeep). Asserting simply that
        // the promotion cost of 150 was not charged — i.e. credits > initial - 150.
        $creditsAfter = $this->getCredits();
        $this->assertGreaterThan(
            $initialCredits - self::RANK2_COST,
            $creditsAfter,
            'Promotion cost must not be deducted when the advisor did not promote'
        );
    }

    public function test_promotion_only_fires_once(): void
    {
        // After promotion, subsequent ticks must not charge the cost again.
        $initialCredits = 10_000;
        $this->setCredits($initialCredits);

        // First tick: crosses threshold → promotes to rank 2
        $this->runTick(9530);
        $this->assertEquals(2, $this->getAdvisorRank(), 'Advisor must be rank 2 after first tick');
        $afterFirstTick = $this->getCredits();

        // Second tick: advisor is now rank 2, already past rank-1 threshold.
        // The rank-2 threshold (20 ticks) is far away — no second promotion should fire.
        $this->runTick(9531);
        $afterSecondTick = $this->getCredits();

        // The only difference between ticks should be the recurring upkeep (50 Cr for rank 2)
        // and passive income. The promotion cost (150 Cr) must NOT be charged again.
        $deltaSecondTick = $afterFirstTick - $afterSecondTick;
        $this->assertLessThan(
            self::RANK2_COST,
            $deltaSecondTick,
            'Promotion cost must not be charged a second time after the advisor already promoted'
        );
    }
}
