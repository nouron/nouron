<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick step 9 — Advisor tick increment and rank promotion.
 *
 * Each tick, assigned advisors (colony_id IS NOT NULL and unavailable_until_tick IS NULL)
 * have their active_ticks incremented.
 *
 * When active_ticks reaches a rank threshold, the advisor is promoted:
 *   rank 1 → rank 2: requires 10 active_ticks, costs 150 Cr (one-time)
 *   rank 2 → rank 3: requires 20 active_ticks, costs 400 Cr (one-time)
 *
 * If the player cannot afford the promotion cost it is deferred until next tick.
 *
 * Covered scenarios:
 *  Happy path:
 *  - active_ticks incremented each tick for assigned advisor
 *  - Advisor promotes from rank 1 to 2 on crossing threshold (with sufficient credits)
 *  - Advisor promotes from rank 2 to 3 on crossing threshold
 *
 *  Edge cases:
 *  - Promotion deferred when insufficient credits
 *  - Promotion fires exactly once (not re-charged on subsequent ticks)
 *  - unavailable_until_tick set → advisor tick NOT incremented
 *  - Unassigned advisor (colony_id=null) NOT incremented
 *
 *  Adversarial:
 *  - active_ticks must never decrease
 *  - Two advisors on same colony both increment independently
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3 (Bart)
 *   Seeded advisor: personell 35 (engineer), rank=1, active_ticks=0
 *   Config rank_thresholds: [1 => 10, 2 => 20]
 *   Config promotion_costs:  [2 => 150, 3 => 400]
 *
 * Uses tick numbers 11500–11549.
 */
class GameTickAdvisorTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID   = 3;
    private const COLONY_ID = 1;

    // From config/game.php
    private const RANK1_THRESHOLD = 10;
    private const RANK2_THRESHOLD = 20;
    private const RANK2_COST      = 150;
    private const RANK3_COST      = 400;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Remove seeded advisors for a clean slate; each test inserts exactly what it needs
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Auto-increments personell_id to avoid UNIQUE(colony_id, personell_id) violations. */
    private int $nextPersonellId = 35;

    private function insertAdvisor(
        int $rank,
        int $activeTicks,
        ?int $unavailableUntilTick = null,
        ?int $colonyId = self::COLONY_ID
    ): int {
        $personellId = $this->nextPersonellId++;
        return DB::table('advisors')->insertGetId([
            'user_id'                => self::USER_ID,
            'colony_id'              => $colonyId,
            'personell_id'           => $personellId,
            'rank'                   => $rank,
            'active_ticks'           => $activeTicks,
            'unavailable_until_tick' => $unavailableUntilTick,
        ]);
    }

    private function getAdvisor(int $id): object
    {
        return DB::table('advisors')->where('id', $id)->first();
    }

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

    // ── active_ticks increment ─────────────────────────────────────────────────

    /**
     * active_ticks must increase by 1 each tick for an assigned, available advisor.
     */
    public function test_active_ticks_incremented_for_assigned_advisor(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: 5);

        Artisan::call('game:tick', ['--tick' => 11500]);

        $advisor = $this->getAdvisor($id);
        $this->assertEquals(6, (int) $advisor->active_ticks,
            'active_ticks must be incremented by 1 each tick');
    }

    /**
     * active_ticks must NOT be incremented when unavailable_until_tick is set.
     */
    public function test_active_ticks_not_incremented_when_advisor_unavailable(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: 5, unavailableUntilTick: 99999);

        Artisan::call('game:tick', ['--tick' => 11501]);

        $advisor = $this->getAdvisor($id);
        $this->assertEquals(5, (int) $advisor->active_ticks,
            'active_ticks must not change when unavailable_until_tick is set');
    }

    /**
     * active_ticks must NOT be incremented for an unassigned advisor (colony_id=null).
     */
    public function test_active_ticks_not_incremented_for_unassigned_advisor(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: 5, colonyId: null);

        Artisan::call('game:tick', ['--tick' => 11502]);

        $advisor = $this->getAdvisor($id);
        $this->assertEquals(5, (int) $advisor->active_ticks,
            'active_ticks must not change for an unassigned advisor');
    }

    /**
     * Two advisors on the same colony both have their active_ticks incremented independently.
     */
    public function test_two_advisors_both_increment(): void
    {
        $id1 = $this->insertAdvisor(rank: 1, activeTicks: 3);
        $id2 = $this->insertAdvisor(rank: 1, activeTicks: 7);

        Artisan::call('game:tick', ['--tick' => 11503]);

        $this->assertEquals(4, (int) $this->getAdvisor($id1)->active_ticks,
            'First advisor active_ticks must increment');
        $this->assertEquals(8, (int) $this->getAdvisor($id2)->active_ticks,
            'Second advisor active_ticks must increment');
    }

    // ── Rank promotion ─────────────────────────────────────────────────────────

    /**
     * Advisor promotes from rank 1 to rank 2 when active_ticks reaches the threshold
     * and the player can afford the promotion cost.
     *
     * active_ticks = threshold - 1 → after increment = threshold → promotion fires.
     */
    public function test_advisor_promotes_rank1_to_rank2_on_threshold(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD - 1);
        $this->setCredits(10_000);

        Artisan::call('game:tick', ['--tick' => 11510]);

        $advisor = $this->getAdvisor($id);
        $this->assertEquals(2, (int) $advisor->rank,
            'Advisor must promote to rank 2 when active_ticks reaches threshold');
    }

    /**
     * Promotion from rank 1 to 2 charges exactly the configured cost (150 Cr).
     */
    public function test_rank_promotion_charges_correct_cost(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD - 1);
        $this->setCredits(5_000);

        // Determine net income/upkeep delta without promotion for comparison
        // by running a tick where promotion does NOT fire
        $noPromotionId = $this->insertAdvisor(rank: 1, activeTicks: 0); // won't promote
        // We need to compare two runs — simpler: just verify the exact credit delta
        // for the promotion cost after accounting for fixed income/upkeep.

        // Housing=2 → income = 30 + 2×20 = 70; upkeep rank1 for two advisors = 2×10 = 20
        // Net income (no promotion) = 70 - 20 = 50
        // Net income (with promotion) = 70 - 20 - 150 = -100
        // But we have TWO advisors now (rank 1). Let's simplify: remove housing to get known values.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);
        DB::table('advisors')->where('id', $noPromotionId)->delete();

        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11511]);

        $after = $this->getCredits();
        // income=30 (nexus), upkeep rank1=10, promotion cost=150
        // delta = 30 - 10 - 150 = -130
        $expected = $before + 30 - 10 - self::RANK2_COST;
        $this->assertEquals($expected, $after,
            'Promotion must charge exactly the configured rank-2 cost (150 Cr)');
    }

    /**
     * Promotion is deferred when the player cannot afford the promotion cost.
     * Advisor stays at rank 1 when credits < 150.
     */
    public function test_promotion_deferred_when_insufficient_credits(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD - 1);
        // Ensure credits will always be below 150 even after income:
        // income = 30 + 2×20 = 70; needed: credits + 70 < 150 → credits < 80
        $this->setCredits(0);

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);

        Artisan::call('game:tick', ['--tick' => 11512]);

        // After tick: income = 30, upkeep = 10, credits = 0+30-10 = 20 (< 150)
        $advisor = $this->getAdvisor($id);
        $this->assertEquals(1, (int) $advisor->rank,
            'Advisor must remain at rank 1 when player cannot afford promotion cost');
    }

    /**
     * Promotion fires exactly once — subsequent ticks do not re-charge the cost.
     */
    public function test_promotion_fires_only_once(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD - 1);
        $this->setCredits(10_000);

        // First tick: crosses threshold → promotes to rank 2
        Artisan::call('game:tick', ['--tick' => 11520]);
        $this->assertEquals(2, (int) $this->getAdvisor($id)->rank, 'Must promote on first crossing');
        $afterTick1 = $this->getCredits();

        // Second tick: already rank 2, rank-2 threshold (20 ticks) is far away
        Artisan::call('game:tick', ['--tick' => 11521]);
        $afterTick2 = $this->getCredits();

        // Delta from tick2: income - upkeep (rank 2 = 50 Cr). No 150 Cr promotion.
        $deltaSecond = $afterTick1 - $afterTick2;
        $this->assertLessThan(self::RANK2_COST, $deltaSecond,
            'Promotion cost must not be charged a second time after promotion is complete');
    }

    /**
     * Advisor promotes from rank 2 to rank 3 on the rank-2 threshold.
     */
    public function test_advisor_promotes_rank2_to_rank3_on_threshold(): void
    {
        $id = $this->insertAdvisor(rank: 2, activeTicks: self::RANK2_THRESHOLD - 1);
        $this->setCredits(10_000);

        Artisan::call('game:tick', ['--tick' => 11530]);

        $advisor = $this->getAdvisor($id);
        $this->assertEquals(3, (int) $advisor->rank,
            'Advisor must promote to rank 3 when active_ticks reaches rank-2 threshold');
    }

    /**
     * active_ticks must not decrease under any circumstances.
     */
    public function test_active_ticks_never_decrease(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: 15);

        Artisan::call('game:tick', ['--tick' => 11540]);

        $advisor = $this->getAdvisor($id);
        $this->assertGreaterThanOrEqual(15, (int) $advisor->active_ticks,
            'active_ticks must never decrease after a tick');
    }
}
